<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\Components\ChildMenuItem;
use PelemanProductUploader\Includes\MegaMenu\Components\MenuItem;
use PelemanProductUploader\Includes\MegaMenu\Components\RootMenuItem;

class MenuItemFactory
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
        if ($item->is_heading_text()) {
            return $this->createHeaderMenuItem($item);
        }

        error_log("Could not create nav menu item: {$item->menu_item_name}");
        return null;

        // throw new \Exception("error defining menu item type");
    }

    private function createBaseItem(InputItem $input): MenuItem
    {
        if (!empty($input->get_parent_menu_name())) {
            return ChildMenuItem::create_new($input);
        }
        return RootMenuItem::create_new($input)->add_css_classes('disablelink');
    }

    private function CreateCategoryItem(InputItem $item): ?MenuItem
    {
        $term = get_term_by('slug', $item->get_category_slug(), 'product_cat');
        if ($term === false) {
            // throw new \Exception("Error finding category");
            return null;
        }

        $navItem = $this->createBaseItem($item);
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

        $navItem = $this->createBaseItem($item);
        $navItem
            ->set_type('post_type')
            ->set_object('product')
            ->set_object_id($productId);
        return $navItem;
    }

    private function createCustomMenuItem(InputItem $item): ?MenuItem
    {
        $navItem = $this->createBaseItem($item);
        $navItem
            ->set_type('custom')
            ->set_item_url($item->get_custom_url());
        return $navItem;
    }

    private function createHeaderMenuItem(InputItem $item): ?MenuItem
    {
        $navItem = $this->createBaseItem($item);
        $navItem->set_type('custom');
        return $navItem;
    }
}
