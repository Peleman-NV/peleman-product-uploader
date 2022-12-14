<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu\Components;

use PelemanProductUploader\Includes\MegaMenu\Components\MenuItem;
use PelemanProductUploader\Includes\MegaMenu\InputItem;

final class RootMenuItem extends MenuItem
{

    public static function create_new(InputItem $input): self
    {
        $instance = new RootMenuItem($input->get_menu_item_name(), $input->get_menu_item_name(), $input->get_position(), $input->get_parent_menu_name());
        $instance->add_input($input);
        return $instance;
    }

    public function generate_settings(): array
    {
        if (empty($this->input)) {
            return [];
        }
        $MenuItemColumns = [];
        $columnWidths = $this->input->get_column_widths();

        // divvy up into columns
        $MenuItemGroups = [
            'columnOne' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 1),
            'columnTwo' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 2),
            'columnThree' => $this->divideIntoArrayOnColumnNumber($MenuItemColumns, 3),
        ];

        $navColumnItemArray = [];
        $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnArray($columnWidths['one'], $MenuItemGroups['columnOne']);
        if (!empty($MenuItemGroups['columnTwo'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnArray($columnWidths['two'], $MenuItemGroups['columnTwo']);
        if (!empty($MenuItemGroups['columnThree'])) $navColumnItemArray[] = $this->createMegaMenuParentObjectColumnArray($columnWidths['three'], $MenuItemGroups['columnThree']);

        $imageSwapWidgetName = $this->updateMegaMenuImageSwapWidgets($MenuItemGroups['columnOne']);

        $navColumnItemArray[] = [
            "meta" => [
                "span" => strval($columnWidths['four']),
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

    /**
     * Undocumented function
     *
     * @param MenuItem[] $array
     * @param integer $columnNumber
     * @param integer $maxColumns
     * @return array
     */
    private function divideIntoArrayOnColumnNumber(array $array, int $columnNumber, int $maxColumns = 3): array
    {
        $finalArray = [];
        foreach ($array as $key => $element) {
            if ($element->get_column_number() === $columnNumber) {
                $finalArray[$key] = $element;
            }
        }
        return $finalArray;
    }

    private function createMegaMenuParentObjectColumnArray(int $columnWidth, array $columnObjectArray): array
    {
        $columnObjectItemsArray = [];
        $columnObjectItems = [
            "meta" => [
                "span" => strval($columnWidth),
                "class" => "",
                "hide-on-desktop" => "false",
                "hide-on-mobile" => "false",
            ],
            "items" => $columnObjectItemsArray
        ];
        foreach ($columnObjectArray as $key => $item) {
            $columnObjectItemsArray[] = $this->createMegaMenuParentObjectColumnObjectItemArray((string)$key);
        }

        $columnObjetItems['items'] = $columnObjectItemsArray;
        return $columnObjectItems;
    }

    private function createMegaMenuParentObjectColumnObjectItemArray(string $menuObjectId)
    {
        return [
            "id" => $menuObjectId,
            "type" => "item"
        ];
    }

    private function updateMegaMenuImageSwapWidgets(array $columnArray): string
    {
        return '';
        $firstChildImageId = $this->getFirstChildImageId($columnArray);

        $megaMenuImageSwapWidgets = get_option('widget_maxmegamenu_image_swap', true);
        error_log(print_r($megaMenuImageSwapWidgets, true));
        $megaMenuImageSwapWidgets = $megaMenuImageSwapWidgets ?? [];
        $megaMenuImageSwapWidgets[] = [
            'media_file_id' => $firstChildImageId,
            'media_file_size' => 'full',
            'wpml_language' => 'all',
        ];
        update_option('widget_maxmegamenu_image_swap', $megaMenuImageSwapWidgets);

        return 'maxmegamenu_image_swap-' . array_key_last($megaMenuImageSwapWidgets);
    }

    private function getFirstChildImageId(array $columnArray): string
    {
        foreach ($columnArray as $arrayElement) {
            if (is_null($arrayElement->product_sku) || empty($arrayElement->product_sku)) {
                continue;
            } else {
                $product = wc_get_product(wc_get_product_id_by_sku($arrayElement->product_sku));

                return $product->get_image_id();
            }
        }
        return '';
    }
}
