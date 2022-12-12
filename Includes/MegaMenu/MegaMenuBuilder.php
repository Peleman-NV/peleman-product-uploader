<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\Response;
use wpdb;

class MegaMenuBuilder
{
    private NavMenuItemBuilder $navItemBuilder;
    /**
     * Creates a WordPress menu
     * 
     * Creating a menu and a megamenu are 2 seperate things, but since a megamenu needs a menu,
     * the menu is created first.
     */
    public function __construct()
    {
        $this->navItemBuilder = new NavMenuItemBuilder();
    }

    public function handleMenuUpload(object $menu): void
    {

        // error_log(print_r(wp_get_nav_menu_items(557),true));
        $response = new Response();
        $createdMenus = [];
        $items = $menu->items;
        $isTranslatedMenu = $menu->lang !== '' && $menu->lang !== 'en';

        $createdMenu = $this->createMenuContainer($menu->name);

        if (!$createdMenu) {
            $response->setError("Error creating menu container");
            wp_send_json($response, 400);
        }

        $createdMenus[] = [
            'id'    => $createdMenu->get_id(),
            'name'  => $createdMenu->get_name(),
            'lang'  => $menu->lang ?: 'en'
        ];

        $items = $this->AddKeysToItemArray($items);
        $objects = array();

        //TODO:: unclutter this unholy mess
        foreach ($items as $slug => $item) {
            try {
                if (isset($item->parent_menu_item_name)) {
                    $objects[$slug]['parent'] = $item->parent_menu_item_name;
                }
                $navItem = $this->navItemBuilder->create_menu_item($item);
                $navItem->set_parent_id($objects[$item->parent_menu_item_name]['id'] ?? 0);
                $objects[$slug]['id'] = wp_update_nav_menu_item(
                    $createdMenu->get_id(),
                    0,
                    $navItem->to_array()
                );
            } catch (\Exception $e) {
                $response->addError(new Response(false, $e->getMessage()));
            }
        }

        //FIXME: this method no longer works the way it did in 5.9.5.
        //in 6.1.1, this returns an empty array. this method only returns a proper next time it is called.
        $currentMenuItems = wp_get_nav_menu_items($createdMenu->get_id());
        //TODO: it is clear that the nav menu items are added properly, but cannot be retrieved within the same execution
        //as such, the fix would be to try and work without these.

        error_log("current menu items: " . print_r($currentMenuItems, true));
        // if (empty($currentMenuItems)) {
        //     $response->setError("No current menu items found.");
        //     wp_send_json($response, 500);
        // }
        $formattedCurrentMenuItemsArray = [];
        foreach ($currentMenuItems as $menuItem) {
            $formattedCurrentMenuItemsArray[$menuItem->title] = $menuItem->ID;
        }

        $finalItemArray = [];
        foreach ($item as $child) { // create child menu items
            $parentId = $formattedCurrentMenuItemsArray[$child->parent_menu_item_name];
            if (!is_int($parentId) || empty($parentId)) {
                $response->setError();
                $response->addError(new NavMenuResponse(
                    false,
                    "Could not determine parent for \"{$child->menu_item_name}\"",
                    $child
                ));
                continue;
            }

            $result = $this->navItemBuilder->create_menu_item($createdMenu, $child, $parentId);

            // if (!is_int($result)) {
            //     $response->setError();
            //     $response->addError(($result));
            //     $errorsArray[] = $result;
            // }
            $finalItemArray[] = ['childId' => $result, 'parentId' => $parentId];
        }

        // per parent create a parentString
        if ($this->createMegaMenu($currentMenuItems, wp_get_nav_menu_items($createdMenu->get_name())) === false) {
            $response->setError("Error creating mega menu");
        }

        $this->joinCreatedMenus($createdMenus);

        // what does this do?????
        if (!$response->isSuccess() && $response->hasErrors()) {
            $this->deleteAllCreatedMenus($createdMenus);
            wp_send_json($response, 400);
        }

        if ($isTranslatedMenu) {
            $term = get_term_by('name', $menu->parent_menu_name, 'nav_menu');
            $parentMenuId = $term->term_id;
            $this->joinTranslatedMenuWithDefaultMenu($createdMenu->get_id(), $menu->lang, $parentMenuId);
        }

        // set menu as active and vertical
        $locations = get_theme_mod('nav_menu_locations');
        $locations['vertical'] = $createdMenu->get_id();
        set_theme_mod('nav_menu_locations', $locations);

        $response->setMessage('menu(\'s) created successfully');
        $response->addMenus($createdMenus);

        wp_send_json($response, 200);
    }

    /**
     * Creates a menu container (in wp_terms table)
     *
     */
    private function createMenuContainer(string $name): ?MenuContainer
    {
        $menuId = wp_create_nav_menu($name);
        error_log("new menu id: " . print_r($menuId, true));

        if (isset($menuId->errors)) {
            error_log("Errors: " . print_r($menuId->errors, true));
            return null;
        }
        return new MenuContainer($name, $menuId);
    }

    private function joinCreatedMenus(array $menus): void
    {
        global $wpdb;
        if ($wpdb instanceof wpdb) {
            $defaultLanguageMenuArray = [];
            // Get current term_relationships for menu ID's
            $existingRelationships = [];
            foreach ($menus as $menu) {
                if ($menu['lang'] !== 'en') {
                    // get existing terms for secondary languages
                    $tempResult[$menu['lang']] = $wpdb->get_results("SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = {$menu['id']};");
                    $mappedResult[$menu['lang']] = array_map(function ($e) {
                        return $e->object_id;
                    }, $tempResult[$menu['lang']]);
                    $existingRelationships[$menu['lang']] = implode(',', $mappedResult[$menu['lang']]);
                }
                if ($menu['lang'] === 'en') {
                    $defaultLanguageMenuArray = $menu;
                }
            }

            // update menu items langauges
            $stringTranslationsTable = $wpdb->prefix . 'icl_translations';
            foreach ($existingRelationships as $language => $relationshipsString) {
                $updateMenuItemsSql = "UPDATE $stringTranslationsTable SET language_code = '$language', source_language_code = 'en' WHERE element_id in ($relationshipsString);";
                $wpdb->get_results($updateMenuItemsSql);
            }

            $tridSql = "SELECT trid FROM $stringTranslationsTable WHERE language_code = 'en' AND element_id = {$defaultLanguageMenuArray['id']};";
            $trid = $wpdb->get_results($tridSql)[0]->trid;

            // update menu container languages and sync trid's of menu container
            foreach ($menus as $menu) {
                if ($menu['lang'] === 'en') continue;
                $updateMenuContainersSql = "UPDATE $stringTranslationsTable SET language_code = '{$menu['lang']}', trid = $trid, source_language_code = 'en' WHERE element_id = {$menu['id']};";
                $wpdb->get_results($updateMenuContainersSql);
            }
        }
    }

    private function deleteAllCreatedMenus(array $menus): void
    {
        foreach ($menus as $menuItem) {
            wp_delete_nav_menu($menuItem['id']);
        }
    }

    private function createMegaMenu(array $parentItem, array $completeMenuItems): bool
    {
        $menuItemsArray = $this->createArrayOfParentAndChildMenuItems($parentItem, $completeMenuItems);

        foreach ($menuItemsArray as $parentId => $childArray) {
            if (empty($childArray)) continue;

            $parentItemData = get_post_meta($parentId, "peleman_mega_menu", true);
            error_log("parent item data: " . print_r($parentItemData, true));
            if (empty($parentItemData)) {
                continue;
            }
            $crerentSettings = $this->createMegaMenuParentObjectString($childArray, $parentItemData->column_widths);

            // this relies on the existance of the CSS class 'mega-disablelink'
            if ($this->addMenuObjectStringToPostMetaData($parentId, $crerentSettings, ['disablelink']) === false) {
                return false;
            }
            foreach ($childArray as $child) {
                $childSettingsArray = $this->createMegaMenuChildObjectString($child['item']);

                if ($this->addMenuObjectStringToPostMetaData($child['item'], $childSettingsArray) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Creates a post metadata string for a mega menu child item
     *
     * @param int $childId	Child item ID
     * @return array
     */
    private function createMegaMenuChildObjectString(int $childId): array
    {
        $itemType = get_post_meta($childId, '_menu_item_object');
        $jsonItemInformation = get_post_meta($childId, 'peleman_mega_menu', true);
        $isHeading = $jsonItemInformation->heading_text;
        $childImageId = 0;

        if (isset($itemType[0]) && $itemType[0] === 'product') {
            $productId = get_post_meta($childId, '_menu_item_object_id')[0];
            $childImageId = get_post_thumbnail_id($productId);
        }

        $childSettingsArray["type"] = "grid";


        if ($isHeading) {
            if (
                !empty($jsonItemInformation->category_slug)
                || !empty($jsonItemInformation->custom_url)
                || !empty($jsonItemInformation->product_sku)
            ) {
                $childSettingsArray['disable_link'] = 'false';
            } else {
                $childSettingsArray['disable_link'] = 'true';
            }
            $childSettingsArray['styles'] = [
                'enabled' => [
                    'menu_item_link_color' => '#333',
                    'menu_item_link_color_hover' => '#333',
                    'menu_item_link_weight' => 'bold'
                ],
                'disabled' => [
                    'menu_item_link_text_decoration' => 'none',
                    'menu_item_border_color' => 'rgba(51, 51, 51, 0)',
                    'menu_item_background_from' => '#333',
                    'menu_item_background_to' => '#333',
                    'menu_item_background_hover_from' => '#333',
                    'menu_item_background_hover_to' => '#333',
                    'menu_item_link_weight_hover' => 'inherit',
                    'menu_item_font_size' => '0px',
                    'menu_item_link_text_align' => 'left',
                    'menu_item_link_text_transform' => 'none',
                    'menu_item_link_text_decoration_hover' => 'none',
                    'menu_item_border_color_hover' => '#333',
                    'menu_item_border_top' => '0px',
                    'menu_item_border_right' => '0px',
                    'menu_item_border_bottom' => '0px',
                    'menu_item_border_left' => '0px',
                    'menu_item_border_radius_top_left' => '0px',
                    'menu_item_border_radius_top_right' => '0px',
                    'menu_item_border_radius_bottom_right' => '0px',
                    'menu_item_border_radius_bottom_left' => '0px',
                    'menu_item_icon_size' => '0px',
                    'menu_item_icon_color' => '#333',
                    'menu_item_icon_color_hover' => '#333',
                    'menu_item_padding_left' => '0px',
                    'menu_item_padding_right' => '0px',
                    'menu_item_padding_bottom' => '0px',
                    'menu_item_margin_left' => '0px',
                    'menu_item_margin_right' => '0px',
                    'menu_item_margin_top' => '0px',
                    'menu_item_margin_bottom' => '0px',
                    'menu_item_height' => '0px',
                    'panel_width' => '0px',
                    'panel_horizontal_offset' => '0px',
                    'panel_vertical_offset' => '0px',
                    'panel_background_from' => '#333',
                    'panel_background_to' => '#333',
                    'panel_background_image' => '0',
                    'panel_background_image_size' => 'auto',
                    'panel_background_image_repeat' => 'no-repeat',
                    'panel_background_image_position' => 'left top'
                ]
            ];
            if ($jsonItemInformation->position !== 1) {
                $childSettingsArray['styles']['enabled']['menu_item_padding_top'] = '10px';
            }
        }
        if ($childImageId !== 0) {
            $childSettingsArray['image_swap'] = [
                "id" => strval($childImageId),
                "size" => "full"
            ];
        }

        return $childSettingsArray;
    }

    /**
     * Creates a postmetadata string for a mega menu parent item
     *
     * @param array $navMenuparentItem	Array of post item IDs aka nav menu item IDs
     */
    private function createMegaMenuParentObjectString(array $navMenuChildItems, object $columnWidths /*, $imageSwapWidgetName*/): array
    {
        // add JSON item data to elements
        $navMenuItemColumns = [];
        foreach ($navMenuChildItems as $navMenuItem) {
            $navMenuItemColumns[$navMenuItem['item']] = get_post_meta($navMenuItem['item'], "peleman_mega_menu", true);
        }

        // divvy up into columns
        $navMenuItemGroups = [
            'columnOne' => $this->divideIntoArrayOnColumnNumber($navMenuItemColumns, 1),
            'columnTwo' => $this->divideIntoArrayOnColumnNumber($navMenuItemColumns, 2),
            'columnThree' => $this->divideIntoArrayOnColumnNumber($navMenuItemColumns, 3),
        ];

        $navColumnItemArray = [];
        $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->one, $navMenuItemGroups['columnOne']);
        if (!empty($navMenuItemGroups['columnTwo'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->two, $navMenuItemGroups['columnTwo']);
        if (!empty($navMenuItemGroups['columnThree'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->three, $navMenuItemGroups['columnThree']);

        $imageSwapWidgetName = $this->updateMegaMenuImageSwapWidgets($navMenuItemGroups['columnOne']);

        $navColumnItemArray[] = [
            "meta" => [
                "span" => strval($columnWidths->four),
                "class" => "",
                "hide-on-desktop" => "false",
                "hide-on-mobile" => "false",
            ],
            "items" => [
                [
                    "id" => $imageSwapWidgetName,
                    "type" => "widget"
                ]
            ]
        ];

        $settings = [
            "type" => "grid",
            "item_align" => "left",
            "icon_position" => "left",
            "sticky_visibility" => "always",
            "align" => "bottom-right",
            "hide_text" => "false",
            "disable_link" => "false",
            "hide_arrow" => "false",
            "hide_on_mobile" => "false",
            "hide_on_desktop" => "false",
            "close_after_click" => "false",
            "hide_sub_menu_on_mobile" => "false",
            "collapse_children" => "false",
            "grid" => [
                [
                    "meta" => [
                        "class" => "",
                        "hide-on-desktop" => "false",
                        "hide-on-mobile" => "false",
                        "columns" => "12",
                    ],
                    "columns" => $navColumnItemArray
                ]
            ]
        ];

        return $settings;
    }

    /**
     * A helper function that returns an array of parent item ID's keys with as value, an array of their child ID's & image ID's if present
     *
     * @param array $parentItem
     * @param array $completeMenuItems
     * @return array
     */
    private function createArrayOfParentAndChildMenuItems(array $parentItem, array $completeMenuItems): array
    {
        // reduce array of parent items to array of parent item ID's
        $parentIdArray = array_map(function ($el) {
            return $el->ID;
        }, $parentItem);

        // take previous array and create array with parent ID's as key with empty arrays as values
        $menuItemsArray = [];
        foreach ($parentIdArray as $key => $value) {
            $menuItemsArray[$value] = [];
        }

        // loop over each menu item Id and, if it's parent is matched in the parentItemIdArray,
        // push IT'S Id under that parent
        foreach ($completeMenuItems as $menuItem) {
            if (!isset($menuItemsArray[$menuItem->menu_item_parent])) {
                error_log("item not found: {$menuItem->menu_item_parent}");
                continue;
            }
            array_push($menuItemsArray[$menuItem->menu_item_parent], ['item' => $menuItem->ID]);
        }

        return $menuItemsArray;
    }

    private function updateMegaMenuImageSwapWidgets(array $columns): string
    {
        $firstChildImageId = $this->getFirstChildImageId($columns);

        $megaMenuImageSwapWidgets = get_option('widget_maxmegamenu_image_swap', true);
        array_push($megaMenuImageSwapWidgets, [
            'media_file_id' => $firstChildImageId,
            'media_file_size' => 'full',
            'wpml_language' => 'all',
        ]);
        update_option('widget_maxmegamenu_image_swap', $megaMenuImageSwapWidgets);

        return 'maxmegamenu_image_swap-' . array_key_last($megaMenuImageSwapWidgets);
    }

    /**
     * Given an array of child items, get the first image ID from it
     *
     * @param array $childArray
     */
    private function getFirstChildImageId(array $columns): ?string
    {
        foreach ($columns as $arrayElement) {
            if (is_null($arrayElement->product_sku) || empty($arrayElement->product_sku)) {
                continue;
            } else {
                $product = wc_get_product(wc_get_product_id_by_sku($arrayElement->product_sku));

                return $product->get_image_id();
            }
        }
    }

    private function createMegaMenuParentObjectColumnString(string $columnWidth, array $columnObjects): array
    {
        $columnObjectItemsArray = [];
        foreach ($columnObjects as $key => $columnObjectItem) {
            $columnObjectItemsArray[] = $this->createMegaMenuParentObjectColumnObjectItemArray($key);
        }
        $columnObjectItems = [
            "meta" => [
                "span" => strval($columnWidth),
                "class" => "",
                "hide-on-desktop" => "false",
                "hide-on-mobile" => "false",
            ],
            "items" => $columnObjectItemsArray
        ];

        return $columnObjectItems;
    }

    private function createMegaMenuParentObjectColumnObjectItemArray(string $menuObjectId): array
    {
        return [
            "id" => strval($menuObjectId),
            "type" => "item"
        ];
    }

    private function joinTranslatedMenuWithDefaultMenu(int $createdMenuId, string $menuLanguage, int $parentMenuId): void
    {
        global $wpdb;
        $sql = "SELECT trid FROM {$wpdb->prefix}icl_translations where element_id = " . $parentMenuId . " and element_type = \"tax_nav_menu\";";
        $result = $wpdb->get_results($sql);
        $trid = $result[0]->trid;
        $updateQuery = "UPDATE {$wpdb->prefix}icl_translations SET trid = " . $trid . ", language_code = \"" . $menuLanguage . "\", source_language_code = \"en\" WHERE element_id = " . $createdMenuId . " AND element_type = \"tax_nav_menu\";";
        $wpdb->get_results($updateQuery);
    }

    /**
     * Updates a nav menu item's metadata
     *
     * @param int $id
     * @param array $settings
     * @param array $cssClasses
     * @return void
     */
    private function addMenuObjectStringToPostMetaData(int $id, array $settings, array $cssClasses = ['']): void
    {
        update_post_meta($id, '_menu_item_megamenu_col', 'columns-2');
        update_post_meta($id, '_menu_item_classes', $cssClasses);
        update_post_meta($id, '_menu_item_megamenu_col_tab', 'columns-1');
        update_post_meta($id, '_menu_item_megamenu_icon_alignment', 'left');
        update_post_meta($id, '_menu_item_megamenu_icon_size', 13);
        update_post_meta($id, '_menu_item_megamenu_style', 'menu_style_column');
        update_post_meta($id, '_menu_item_megamenu_widgetarea', 0);
        update_post_meta($id, '_menu_item_megamenu_background_image', '');
        update_post_meta($id, '_menu_item_megamenu_icon', '');
        update_post_meta($id, '_menu_item_megamenu_icon_color', '');
        update_post_meta($id, '_menu_item_megamenu_sublabel', '');
        update_post_meta($id, '_menu_item_megamenu_sublabel_color', '');
        update_post_meta($id, '_megamenu', $settings);
    }

    private function divideIntoArrayOnColumnNumber(array $array, int $columnNumber): array
    {
        $finalArray = [];
        foreach ($array as $arrayKey => $arrayElement) {
            if ($arrayElement->column_number < 1 || $arrayElement->column_number > 3) {
                $response['status'] = 'error';
                $response['message'] = "column_number must be 1,2, or 3";
                $response['item'] = $arrayElement->menu_item_name;
                $response['column_number'] = $arrayElement->column_number;
                wp_send_json($response, 400);
            }
            if ($arrayElement->column_number === $columnNumber) {
                $finalArray[$arrayKey] = $arrayElement;
            }
        }
        return $finalArray;
    }

    private function AddKeysToItemArray(array $items): array
    {
        $slugItems = [];
        foreach ($items as $item) {
            $slugItems[$item->menu_item_name] = $item;
        }
        return $slugItems;
    }
}
