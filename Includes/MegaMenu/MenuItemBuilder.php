<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\MenuItem;

class MenuItemBuilder
{
    public function __construct()
    {
    }

    public function create_menu_item(InputItem $item): ?MenuItem
    {
        if ($item->is_category_item()) {
            return $this->CreateCategoryItem($item);
        }
        if ($item->is_product_item()) {
            return $this->createProductItem($item);
        }
        if ($item->is_custom_item()) {
            return $this->createCustomMenuItem($item);
        }

        error_log("Could not create nav menu item: {$item->menu_item_name}");
        return null;

        // throw new \Exception("error defining menu item type");
    }

    private function createDefaultItem(InputItem $item): MenuItem
    {
        return new MenuItem(
            $item->get_menu_item_name(),
            $item->get_menu_item_name(),
            $item->get_position(),
            $item->get_parent_menu_name()
        );
    }

    private function CreateCategoryItem(InputItem $item): ?MenuItem
    {
        // category menu item
        $term = get_term_by('slug', $item->get_category_slug(), 'product_cat');
        if ($term === false) {
            // throw new \Exception("Error finding category");
            return null;
        }
        $navItem = $this->createDefaultItem($item);
        $navItem
            ->set_type('taxonomy')
            ->set_object($term->taxonomy)
            ->set_object_id($term->term_id);
        return $navItem;
    }

    private function createProductItem(InputItem $item): ?MenuItem
    {
        $productId = wc_get_product_id_by_sku($item->get_product_sku());
        if ($productId === 0) {
            // throw new \Exception("error finding product");menu
            return null;
        }
        $navItem = $this->createDefaultItem($item);
        $navItem
            ->set_type('post_type')
            ->set_object('product')
            ->set_object_id($productId);
        return $navItem;
    }

    private function createCustomMenuItem(InputItem $item): ?MenuItem
    {
        $navItem = $this->createDefaultItem($item);
        $navItem
            ->set_type('custom')
            ->set_item_url($item->get_custom_url());
        return $navItem;
    }
}
