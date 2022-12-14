<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class MenuItemTree
{
    /** @var string[] */
    private array $parentKeys;
    /** @var MenuItem[] */
    private array $items;

    public function construct()
    {
    }

    public function initialize_from_flat_array(array $items)
    {
        $objects = $this->create_objects_from_items($items);
        foreach ($objects as $key => $object) {
            $parent = $object->get_parent_title();
            if (empty($parent)) {
                $this->parentKeys[] = $parent;
                $this->items[$key] = $object;
                continue;
            }
            $objects[$key] = $object;
            $this->items[$parent]->add_child_element($object);
        }
    }

    /**
     * converts a list/array of API input items into parented MenuItem object trees/8523*96
     *
     * @param array $items
     * @return array
     */
    private function convert_items_to_object_trees(array $items): array
    {
        return $this->parent_objects_in_array($this->create_objects_from_items($items));
    }

    /**
     * convert each item to an object, and put in an associative array with name as key
     *
     * @param object[] $items
     * @return MenuItem[]
     */
    private function create_objects_from_items(array $items): array
    {
        $objects = [];
        $builder = new MenuItemBuilder();
        //first loop: convert each item to an object, and put in a dictionary with name as key
        foreach ($items as $item) {
            $key = $item['menu_item_name'];
            $object = $builder->create_menu_item((object)$item);
            if (empty($object)) {
                error_log("error creating new object: {$key}");
                continue;
            }
            $objects[$key] = $object;
        }
        return $objects;
    }

    /**
     * second loop: insert children into parents objects
     *
     * @param MenuItem[] $items
     * @return MenuItem[]
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
}
