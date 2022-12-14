<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu\Components;

use PelemanProductUploader\Includes\MegaMenu\Components\MenuItem;
use PelemanProductUploader\Includes\MegaMenu\InputItem;

final class ChildMenuItem extends MenuItem
{
    public static function create_new(InputItem $input): self
    {
        $instance = new ChildMenuItem($input->get_menu_item_name(), $input->get_menu_item_name(), $input->get_position(), $input->get_parent_menu_name());
        $instance->add_input($input);
        return $instance;
    }

    protected function generate_settings(): array
    {
        $childId = $this->db_id;
        if (!$childId) {
            return [];
        }
        $itemType = get_post_meta($childId, '_menu_item_object');
        $input = $this->input;
        $isHeading = $input->get_is_heading_text();
        $childImageId = 0;

        if (isset($itemType[0]) && $itemType[0] === 'product') {
            $productId = get_post_meta($childId, '_menu_item_object_id')[0];
            $childImageId = get_post_thumbnail_id($productId);
        }

        $settings["type"] = "grid";


        if ($isHeading) {
            if (
                !empty($input->get_category_slug())
                || !empty($input->get_custom_url())
                || !empty($input->get_product_sku())
            ) {
                $settings['disable_link'] = 'false';
            } else {
                $settings['disable_link'] = 'true';
            }
            $settings['styles'] = [
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
            if ($input->get_position() !== 1) {
                $childSettingsArray['styles']['enabled']['menu_item_padding_top'] = '10px';
            }
        }
        if ($childImageId !== 0) {
            $settings['image_swap'] = [
                "id" => strval($childImageId),
                "size" => "full"
            ];
        }

        return $settings;
    }
}
