<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\MenuContainer;
use PelemanProductUploader\Includes\MegaMenu\MenuItem;
use PelemanProductUploader\Includes\MegaMenu\MenuItemBuilder;
use PelemanProductUploader\Includes\MegaMenu\Response;

class MegaMenuCreationEndpoint
{
    public function __construct()
    {
    }

    public function create_new_megamenu(array $request): Response
    {
        $response = new Response();
        $menuName = $request['name'];
        try {
            $items = $request['items'];
            #region early return
            if (!is_array($items)) {
                return new Response(false, "incorrect parameter: items is not an array", 400);
            }
            if (get_term_by('name', $menuName, 'nav_menu')) {
                return new Response(false, "menu with name {$menuName} already exists. please try another.", 400);
            }
            #endregion

            $objectTrees = $this->convert_items_to_object_trees($items);
            // $this->create_new_menu($menuName, $request['lang'])->add_nav_menu_items($objectTrees);

            return new Response(true, "ding");
        } catch (\Exception $e) {
            $response->setError($e->getMessage());
            error_log((string)$e);
            return $response;
        }

        return $response;
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
     * @return array
     */
    private function convert_items_to_object_trees(array $items): array
    {
        return $this->parent_objects_in_array(
            $this->create_objects_from_items($items)
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
            if (empty($parent)) {
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
    private function create_objects_from_items(array $items): array
    {
        $objects = [];
        $builder = new MenuItemBuilder();

        foreach ($items as $item) {
            $input = new InputItem($item);
            $key = $input->get_menu_item_name();
            $object = $builder->create_menu_item($input);
            if (empty($object)) {
                error_log("error creating new object: {$key}");
                continue;
            }
            $objects[$key] = $object;
        }
        return $objects;
    }
}
