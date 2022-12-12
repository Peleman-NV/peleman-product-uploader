<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\NavMenuItem;
use PelemanProductUploader\Includes\Response;

class NavMenuItemBuilder
{
    public function __construct()
    {
    }

    public function create_menu_item(object $item): NavMenuItem
    {
        $menuObject = new NavMenuItem($item->menu_item_name);
        $menuObject
            ->set_position($item->position ?? 0)
            ->set_status('publish')
            ->set_attr_title($item->menu_item_name);

        if (!empty($item->category_slug) && empty($item->custom_url) && empty($item->product_sku)) {
            $this->CreateCategoryItem($menuObject, $item);
            return $menuObject;
        }
        if (empty($item->category_slug) && empty($item->custom_url) && !empty($item->product_sku)) {
            $this->createProductItem($menuObject, $item);
            return $menuObject;
        }
        if (empty($item->category_slug) && !empty($item->custom_url) && empty($item->product_sku) || $item->heading_text === true) {
            // custom menu item
            $this->createCustomMenuItem($menuObject, $item);
            return $menuObject;
        }
        throw new \Exception("error defining menu item type");
    }

    private function CreateCategoryItem(NavMenuItem $navItem, object $item): void
    {
        // category menu item
        $term = get_term_by('slug', $item->category_slug, 'product_cat');
        if ($term === false) {
            throw new \Exception("Error finding category");
        }

        $navItem
            ->set_type('taxonomy')
            ->set_object($term->taxonomy)
            ->set_object_id($term->term_id);
    }

    private function createProductItem(NavMenUItem $navItem, object $item): void
    {
        $productId = wc_get_product_id_by_sku($item->product_sku);
        if ($productId === 0) {
            throw new \Exception("error finding product");
        }
        $navItem
            ->set_type('post_type')
            ->set_object('product')
            ->set_object_id($productId);
    }

    private function createCustomMenuItem(NavMenuItem $navItem, object $item): void
    {
        $navItem
            ->set_type('custom')
            ->set_item_url($item->custom_url);
    }

    private function TryAddNavItemToMenu(MenuContainer $menu, NavMenuItem $item): NavMenuResponse
    {
        $item = wp_update_nav_menu_item(
            $menu->get_id(),
            0,
            $item->to_array()
        );
        error_log("what is this fucking response, JASON??? " . print_r($item, true));
        // save upload item information to items metadata to use later
        update_post_meta($item, 'peleman_mega_menu', $item);

        return new NavMenuResponse(true, "", $item);
    }
}
