<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class ChildSettingArrayBuilder
{
    public function createChildSettingsArray(int $childId): array
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
}
