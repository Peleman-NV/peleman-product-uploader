<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class MenuContainer
{
    private string $name;
    private int $id;
    private string $lang;

    /** @var MenuItem[] */
    private array $navMenuTree;

    public function __construct(string $name, int $id, string $lang = 'en')
    {
        $this->name = $name;
        $this->id = $id;
        $this->lang = $lang;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_lang(): string
    {
        return $this->lang;
    }

    /**
     * add an array of MenuItems to the menu
     * @param MenuItem[] $objects
     * @return void
     */
    public function add_nav_menu_items(array $objects): void
    {        // initialize all objects and add to menu
        foreach ($objects as $object) {
            try {
                $object->add_to_menu($this);
            } catch (\Exception $e) {
                error_log((string)$e);
            }
        }
    }
}
