
<?php

use PelemanProductUploader\Includes\MegaMenu\TranslatedMenuJoiner;

class TragicAdminMenu
{
    public function handleMenuUpload($menu)
    {
        #region create menu and nav items
        $response = [];
        $errorsArray = [];
        //there is only ever one menu created, so this array is superfluous
        $createdMenus = [];
        $items = $menu->items;
        $isTranslatedMenu = $menu->lang !== '' && $menu->lang !== 'en';

        if ($createdMenu = $this->createMenuContainer($menu->name)) { // create menu container
            $menuId = $createdMenu['menu_id'];
            $menuName = $createdMenu['name'];
            if ($menu->lang === '') $createdMenus[] = ['id' => $menuId, 'name' => $menuName, 'lang' => 'en'];
            if ($menu->lang !== '') $createdMenus[] = ['id' => $menuId, 'name' => $menuName, 'lang' => $menu->lang];
        } else {
            $response['status'] = 'error';
            $response['message'] = "Error creating menu container";
            wp_send_json($response, 400);
        }

        // divvy items up into parent- and child arrays depending on parent_item_menu_name being empty or not
        $parentItems = array_filter($items, function ($item) {
            return $item->parent_menu_item_name === '';
        });
        $childItems = array_filter($items, function ($item) {
            return $item->parent_menu_item_name !== '';
        });

        foreach ($parentItems as $parent) { // create parent menu items
            $result = $this->create_menu_item($menuId, $parent);
            if (!is_int($result)) {
                $response['status'] = 'error';
                $errorsArray[] = $result;
            }
        }

        $currentMenuItems = wp_get_nav_menu_items($menuName);
        $formattedCurrentMenuItemsArray = [];
        foreach ($currentMenuItems as $menuItem) {
            $formattedCurrentMenuItemsArray[$menuItem->title] = $menuItem->ID;
        }

        $finalItemArray = [];
        foreach ($childItems as $child) { // create child menu items
            $parentId = $formattedCurrentMenuItemsArray[$child->parent_menu_item_name];
            if (!is_int($parentId) || empty($parentId)) {
                $response['status'] = 'error';
                $errorsArray[] = [
                    'message' => "Could not determine parent for \"{$child->menu_item_name}\"",
                    'item' => $child,
                ];
                continue;
            }

            $result = $this->create_menu_item($menuId, $child, $parentId);

            if (!is_int($result)) {
                $response['status'] = 'error';
                $errorsArray[] = $result;
            }
            $finalItemArray[] = ['childId' => $result, 'parentId' => $parentId];
        }

        // per parent create a parentString
        if ($this->createMegaMenu($currentMenuItems, wp_get_nav_menu_items($menuName)) === false) {
            $response['status'] = 'error';
            $response['message'] = "Error creating mega menu";
        }
        #endregion

        #region join translated menus via WPML

        //this is just not being used because the array only contains a singular element
        $this->joinCreatedMenus($createdMenus);
        $response['errors'] = $errorsArray;

        #region delete menus if something goes wrong

        // what does this do?????
        if (isset($response['error']) && !empty($response['error'])) {
            $this->deleteAllCreatedMenus($createdMenus);
            wp_send_json($response, 400);
        }

        #endregion
        if ($isTranslatedMenu) {
            $term = get_term_by('name', $menu->parent_menu_name, 'nav_menu');
            $parentMenuId = $term->term_id;
            // join current table with parent
            $this->joinTranslatedMenuWithDefaultMenu($menuId, $menu->lang, $parentMenuId);
        }

        #endregion

        // set menu as active and vertical
        $locations = get_theme_mod('nav_menu_locations');
        $locations['vertical'] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);

        $response['status'] = 'success';
        $response['message'] = 'menu(\'s) created successfully';
        $response['menus'] = $createdMenus;

        wp_send_json($response, 200);
    }

    private function joinTranslatedMenuWithDefaultMenu(int $createdMenuId, string $menuLanguage, int $parentMenuId)
    {
        global $wpdb;
        $joiner = new TranslatedMenuJoiner($wpdb);
        $joiner->joinTranslatedMenuWithDefaultMenu($createdMenuId, $menuLanguage, $parentMenuId);
    }
    /**
     * Creates a menu container (in wp_terms table)
     *
     * @param object $menu_name
     * @return array
     */
    private function createMenuContainer($name)
    {
        if (empty($name)) return false;
        $menuId = wp_create_nav_menu($name);

        if (isset($menuId->errors)) {
            return false;
        }
        return ['menu_id' => $menuId, 'name' => $name];
    }

    private function joinCreatedMenus($menus): void
    {
        global $wpdb;
        $joiner = new TranslatedMenuJoiner($wpdb);
        $joiner->joinCreatedMenus($menus);
    }


    private function deleteAllCreatedMenus($menuArray)
    {
        foreach ($menuArray as $menuItem) {
            wp_delete_nav_menu($menuItem['id']);
        }
    }

    /**
     * Creates a menu item
     *
     * @param int $menuId
     * @param object $item
     * @param integer $parentId
     * @return void
     */
    private function create_menu_item($menuId, $item, $parentId = 0)
    {

        if ((!is_int($item->position) || empty($item->position)) && $item->position !== 0) {
            $response['status'] = 'error';
            $response = [
                'message' => "Could not determine position for \"{$item->menu_item_name}\"",
                'item' => $item
            ];
            return $response;
        }

        $menuObject = [
            'menu-item-position' => $item->position,
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $parentId,
            'menu-item-title' => $item->menu_item_name, // display name
            'menu-item-attr-title' => $item->menu_item_name, // css title attribute
        ];

        if (!empty($item->category_slug) && empty($item->custom_url) && empty($item->product_sku)) {
            // category menu item
            $term = get_term_by('slug', $item->category_slug, 'product_cat');
            if ($term === false) {
                $response['message'] = "Error finding category";
                $response['item'] = $item;
                return $response;
            }

            $menuObject['menu-item-type'] = 'taxonomy';
            $menuObject['menu-item-object'] = $term->taxonomy;
            $menuObject['menu-item-object-id'] = $term->term_id;
        } else if (empty($item->category_slug) && empty($item->custom_url) && !empty($item->product_sku)) {
            // product menu item
            $productId = wc_get_product_id_by_sku($item->product_sku);
            if ($productId === 0) {
                $response['message'] = "Error finding product";
                $response['item'] = $item;
                return $response;
            }

            $menuObject['menu-item-type'] = 'post_type';
            $menuObject['menu-item-object'] = 'product';
            $menuObject['menu-item-object-id'] = $productId;
        } else if (empty($item->category_slug) && !empty($item->custom_url) && empty($item->product_sku) || $item->heading_text === true) {
            // custom menu item
            $menuObject['menu-item-type'] = 'custom';
            $menuObject['menu-item-url'] = $item->custom_url;
        } else {
            $response['message'] = "Error defining menu item type";
            $response['item'] = $item;
            return $response;
        }

        try {
            $response = wp_update_nav_menu_item(
                $menuId,
                0, // current menu item ID - 0 for new item,
                $menuObject
            );
            // save upload item information to items metadata to use later
            update_post_meta($response, 'peleman_mega_menu', $item);
        } catch (\Throwable $th) {
            $response['message'] = $th->getMessage();
            return $response;
        }

        return $response;
    }

    private function createMegaMenu(array $parentItemArray, array $completeMenuItemArray): bool
    {
        $menuItemsArray = $this->createArrayOfParentAndChildMenuItems($parentItemArray, $completeMenuItemArray);

        foreach ($menuItemsArray as $parentId => $childArray) {
            if (empty($childArray)) continue;

            $parentItemData = get_post_meta($parentId, "peleman_mega_menu", true);
            $parentSettingsArray = $this->createMegaMenuParentObjectArray($childArray, $parentItemData->column_widths);

            // this relies on the existance of the CSS class 'mega-disablelink'
            if ($this->addMenuObjectStringToPostMetaData($parentId, $parentSettingsArray, ['disablelink']) === false) {
                return false;
            }
            foreach ($childArray as $child) {
                $childSettingsArray = $this->createChildSettingsArray($child['item']);

                if ($this->addMenuObjectStringToPostMetaData($child['item'], $childSettingsArray) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Updates a nav menu item's metadata
     *
     * @param int $id
     * @param array $settingsArray
     * @param array $cssClasses
     * @return void
     */
    private function addMenuObjectStringToPostMetaData(int $id, array $settingsArray, array $cssClasses = [''])
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
        update_post_meta($id, '_megamenu', $settingsArray);
    }


    /**
     * Creates a postmetadata string for a mega menu child item
     *
     * @param int $childId Child item ID
     * @return array
     */
    private function createChildSettingsArray(int $childId): array
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

    private function divideIntoArrayOnColumnNumber(array $array, int $columnNumber, int $maxColumns = 3): array
    {
        $finalArray = [];
        foreach ($array as $arrayKey => $arrayElement) {
            if ($arrayElement->column_number < 1 || $arrayElement->column_number > $maxColumns) {
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

    /**
     * Creates a post metadata array for a mega menu parent item
     *
     * @param array $navMenuParentItemArray Array of post item IDs aka nav menu item IDs
     * @return string
     */
    private function createMegaMenuParentObjectArray(array $navMenuChildItemArray, stdClass $columnWidths /*, $imageSwapWidgetName*/): array
    {
        // add JSON item data to elements
        $MenuItemColumns = [];
        foreach ($navMenuChildItemArray as $MenuItem) {
            $MenuItemColumns[$MenuItem['item']] = get_post_meta($MenuItem['item'], "peleman_mega_menu", true);
        }

        // divvy up into columns
        $MenuItemGroups = [
            'columnOne' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 1),
            'columnTwo' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 2),
            'columnThree' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 3),
        ];

        $navColumnItemArray = [];
        $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->one, $MenuItemGroups['columnOne']);
        if (!empty($MenuItemGroups['columnTwo'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->two, $MenuItemGroups['columnTwo']);
        if (!empty($MenuItemGroups['columnThree'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnString($columnWidths->three, $MenuItemGroups['columnThree']);

        $imageSwapWidgetName = $this->updateMegaMenuImageSwapWidgets($MenuItemGroups['columnOne']);

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

        $settingsArray = [
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

        return $settingsArray;
    }

    private function updateMegaMenuImageSwapWidgets($columnArray)
    {
        $firstChildImageId = $this->getFirstChildImageId($columnArray);

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
     * @return int|null
     */
    private function getFirstChildImageId($columnArray)
    {
        foreach ($columnArray as $arrayElement) {
            if (is_null($arrayElement->product_sku) || empty($arrayElement->product_sku)) {
                continue;
            } else {
                $product = wc_get_product(wc_get_product_id_by_sku($arrayElement->product_sku));

                return $product->get_image_id();
            }
        }
    }

    private function createMegaMenuParentObjectColumnString($columnWidth, $columnObjectArray)
    {
        $columnObjectItemsArray = [];
        foreach ($columnObjectArray as $key => $columnObjectItem) {
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

    private function createMegaMenuParentObjectColumnObjectItemArray($menuObjectId)
    {
        return [
            "id" => strval($menuObjectId),
            "type" => "item"
        ];
    }

    /**
     * A helper function that returns an array of parent item ID's keys with as value, an array of their child ID's & image ID's if present
     *
     * @param array $parentItemArray
     * @param array $completeMenuItemArray
     * @return array
     */
    private function createArrayOfParentAndChildMenuItems($parentItemArray, $completeMenuItemArray)
    {
        // reduce array of parent items to array of parent item ID's
        $parentIdArray = array_map(function ($el) {
            return $el->ID;
        }, $parentItemArray);

        // take previous array and create array with parent ID's as key with empty arrays as values
        //this results in an empty array with IDS mapped to empty arrays
        $menuItemsArray = [];
        foreach ($parentIdArray as $value) {
            $menuItemsArray[$value] = [];
        }

        // loop over each menu item Id and, if it's parent is matched in the parentItemIdArray,
        // push IT'S Id under that parent
        foreach ($completeMenuItemArray as $menuItem) {
            array_push($menuItemsArray[$menuItem->menu_item_parent], ['item' => $menuItem->ID]);
        }

        //this creates a tree of parent and child items, I suppose?
        //the problem with this kind of naming is that it obscures the actual structure of the array you are using
        return $menuItemsArray;
    }
}
