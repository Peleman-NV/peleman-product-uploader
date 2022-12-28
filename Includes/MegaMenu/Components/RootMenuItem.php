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
        $instance->css = ['disablelink'];
        return $instance;
    }

    public function generate_settings(): array
    {
        if (empty($this->input)) {
            return [];
        }
        #region columns
        $columnWidths = $this->input->get_column_widths();
        $columnWidths = array_values($columnWidths);

        $navColumnItemArray = [];
        $imageSwapWidgetName = '';
        for ($i = 0; $i < count($columnWidths); $i++) {
            $group = $this->sort_items_into_columns($this->children, $i);
            if (empty($group)) continue;
            $navColumnItemArray[$i] = $this->createMegaMenuParentObjectColumnArray(
                (int)$columnWidths[$i],
                $group,
            );

            if ($i === 0) {
                $imageSwapWidgetName = $this->updateMegaMenuImageSwapWidgets($group);
            }

            $navColumnItemArray[$i] = [
                "meta" => [
                    "span" => $columnWidths[$i],
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
        }

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
     * @return MenuItem[]
     */
    private function sort_items_into_columns(array $array, int $columnNumber, int $maxColumns = 3): array
    {
        $columns = [];
        foreach ($array as $element) {
            if ($element->get_column_number() === $columnNumber) {
                $columns[$element->input->get_position()] = $element;
            }
        }
        return $columns;
    }

    /**
     * Undocumented function
     *
     * @param integer $columnWidth
     * @param MenuItem[] $columnObjectArray
     * @return array
     */
    private function createMegaMenuParentObjectColumnArray(int $columnWidth, array $columnObjectArray): array
    {
        $columnObjectItemsArray = [];
        $columnObjectItems = [
            "meta" => [
                "span" => strval($columnWidth),
                "class" => "",
                "hide-on-desktop" => "false",
                "hide-on-mobile" => "false",
            ]
        ];

        foreach ($columnObjectArray as $item) {
            $columnObjectItemsArray[] = [
                'id' => $item->get_db_id(),
                'type' => 'item'
            ];
        }
        $columnObjectItems['items'] = $columnObjectItemsArray;
        return $columnObjectItems;
    }

    /**
     * Undocumented function
     *
     * @param MenuItem[] $columnArray
     * @return string
     */
    private function updateMegaMenuImageSwapWidgets(array $columnArray): string
    {
        $firstChildImageId = $this->getFirstChildImageId($columnArray);

        $megaMenuImageSwapWidgets = (array)get_option('widget_maxmegamenu_image_swap', true);
        $megaMenuImageSwapWidgets = $megaMenuImageSwapWidgets ?? [];
        // error_log(print_r($megaMenuImageSwapWidgets, true));
        $megaMenuImageSwapWidgets[] = [
            'media_file_id' => $firstChildImageId,
            'media_file_size' => 'full',
            'wpml_language' => 'all',
        ];
        update_option('widget_maxmegamenu_image_swap', $megaMenuImageSwapWidgets);

        return 'maxmegamenu_image_swap-' . array_key_last($megaMenuImageSwapWidgets);
    }

    /**
     * Undocumented function
     *
     * @param MenuItem[] $columnArray
     * @return string
     */
    private function getFirstChildImageId(array $columnArray): string
    {
        foreach ($columnArray as $arrayElement) {
            $sku = $arrayElement->get_product_sku();
            if (!$sku) continue;

            $product = wc_get_product(wc_get_product_id_by_sku($sku));
            if (!$product) continue;

            return $product->get_image_id();
        }
        return '';
    }
}
