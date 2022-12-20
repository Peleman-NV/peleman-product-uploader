<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\MenuContainer;
use PelemanProductUploader\Includes\MegaMenu\Components\MenuItem;
use PelemanProductUploader\Includes\MegaMenu\Components\RootMenuItem;
use PelemanProductUploader\Includes\MegaMenu\MenuItemFactory;
use PelemanProductUploader\Includes\MegaMenu\Response;
use WP_Term;

class MegaMenuCreationEndpoint
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    public function create_new_megamenu(array $request): Response
    {
        $this->response = new Response();
        $menuName = $request['name'];
        try {
            $items = $request['items'];
            #region early return
            if (!is_array($items)) {
                return $this->response->setError("incorrect parameter: items is not an array", 400);
            }
            if (get_term_by('name', $menuName, 'nav_menu')) {
                return $this->response->setError("menu with name {$menuName} already exists. please try another.", 400);
            }
            #endregion

            $objectTrees = $this->convert_items_to_object_trees($items);
            if (!$this->response->isSuccess()) {
                return $this->response
                    ->setMessage("Failed to create nav item elements.")
                    ->setCode(400);
            }

            $menu = $this->create_new_menu($menuName, $request['lang']);
            $menu->add_nav_menu_items($objectTrees);

            foreach ($objectTrees as $tree) {
                $tree->register_settings();
            }

            if (
                $this->is_wpml_active()
                && !empty($menu->get_lang())
                && $menu->get_lang() !== 'en'
            ) {
                $this->Join_menu_translations($menu, $request['parent_menu_name']);
            }

            $this->save_menu_to_location($menu, 'vertical');

            $this->response->setMessage("Menu generated successfully!");
        } catch (\Exception $e) {
            $this->response->setError($e->getMessage());
            error_log((string)$e);
            return $this->response;
        }

        return $this->response;
    }

    private function create_new_menu(string $name, string $lang): MenuContainer
    {
        $menuId = wp_create_nav_menu($name);
        if (\is_wp_error($menuId)) {
            throw new \Exception($menuId->get_error_message());
        }

        return new MenuContainer($name, $menuId, $lang);
    }

    /**
     * converts a list/array of API input items into parented MenuItem object trees
     *
     * @param array $items
     * @return MenuItem[]
     */
    private function convert_items_to_object_trees(array $items): array
    {

        return $this->parent_objects_in_array(
            $this->create_objects_from_inputs($items)
        );
    }

    /**
     * second loop: insert children into parents objects
     *
     * @param MenuItem[] $items
     * @return MenuItem[] a list of parent objects, containing children. this is thus an array of trees
     */
    private function parent_objects_in_array(array $objects): array
    {
        $parents = [];
        foreach ($objects as $key => $object) {
            $parent = $object->get_parent_title();
            if ($object instanceof RootMenuItem && empty($parent)) {
                $parents[$key] = $object;
                continue;
            }
            $objects[$parent]->add_child_element($object);
        }

        //array now contains parented objects.
        return $parents;
    }

    /**
     * convert each item to an object, and put in an associative array with name as key
     *
     * @param array $items
     * @return MenuItem[] associative array of menu item objects, with their menu item name as key
     */
    private function create_objects_from_inputs(array $items): array
    {
        $objects = [];
        $builder = new MenuItemFactory();

        foreach ($items as $item) {
            try {
                $input = new InputItem($item);
                $key = $input->get_menu_item_name();
                $object = $builder->create_menu_item($input);
                if (empty($object)) {
                    error_log("error creating new object: {$key}");
                    continue;
                }
                $objects[$key] = $object;
                $this->response->addResponse(new Response(true, "created {$object->get_menu_item_name()} nav item"));
            } catch (\exception $e) {
                $this->response->addResponse(new Response(false, $e->getMessage(), $e->getCode()));
                continue;
            }
        }
        return $objects;
    }

    private function save_menu_to_location(MenuContainer $menu, string $location = 'primary'): void
    {
        $locations = get_theme_mod('nav_menu_locations');
        error_log("theme locations: " . print_r($locations, true));
        $locations[$location] = $menu->get_id();
        set_theme_mod('nav_menu_locations', $locations);
    }

    /**
     * Attempt to join a menu to its original translation.
     * 
     * will do an early return if no original translation can be found, or original menus
     * conflict with each other
     *
     * @param MenuContainer $menu
     * @param string $parentMenuName
     * @return void
     */
    private function Join_menu_translations(MenuContainer $menu, string $parentMenuName): void
    {
        if (empty($parentMenuName) || $menu->get_lang() === 'en') return;
        global $wpdb;
        $parentMenu = get_term_by('name', $parentMenuName, 'nav_menu');
        if (!($parentMenu instanceof WP_Term)) return;

        $joiner = new TranslatedMenuJoiner($wpdb);
        $joiner->joinTranslatedMenuWithDefaultMenu($menu->get_id(), $menu->get_lang(), $parentMenu->term_id);
    }

    /**
     * helper function - determine if WPML plugin is active.
     *
     * @return boolean
     */
    private function is_wpml_active(): bool
    {
        $active = is_plugin_active('sitepress-multilingual-cms/sitepress.php');
        error_log($active ? "WPML is active" : "WPML is not active");
        return $active;
    }
}
