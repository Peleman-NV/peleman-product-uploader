<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class MenuItem
{
    #region properties
    private string $parent_title;

    private int $db_id;
    private int $object_id;
    private string $object;
    private int $parent_id;
    private int $position;

    private string $type;
    private string $title;
    private string $url;
    private string $description;
    private string $attr_title;
    private string $target;
    private string $classes;
    private string $xfn;
    private string $status;

    /** @var MenuItem[] */
    private array $children;
    #endregion

    public function __construct(string $title, 
    string $attr_title, int $position, string $parent = '', string $status = 'publish')
    {
        $this->title = $title;
        $this->status = $status;
        $this->attr_title = $attr_title;
        $this->position = $position;
        $this->parent_title = $parent;

        $this->db_id = 0;
        $this->object_id = 0;
        $this->object = '';
        $this->parent_id = 0;

        $this->type = 'custom';
        $this->url = '';
        $this->description = '';
        $this->target = '';
        $this->classes = '';
        $this->xfn = '';

        $this->children = [];
    }

    #region build setters
    public function set_position(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function set_status(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function set_parent_id(int $id): self
    {
        $this->parent_id = $id;
        return $this;
    }

    public function set_attr_title(string $title): self
    {
        $this->attr_title = $title;
        return $this;
    }

    public function set_type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function set_object(string $object): self
    {
        $this->object = $object;
        return $this;
    }

    public function set_object_id(int $id): self
    {
        $this->object_id = $id;
        return $this;
    }

    public function set_item_url(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function add_child_element(MenuItem $child): void
    {
        $this->children[] = $child;
    }

    public function set_parent_title(string $parent): void
    {
        $this->parent_title = $parent;
    }

    public function get_parent_title(): string
    {
        return $this->parent_title;
    }

    #endregion
    private function to_array(): array
    {
        return array(
            'menu-item-db-id'         => $this->db_id,
            'menu-item-object-id'     => $this->object_id,
            'menu-item-object'        => $this->object,
            'menu-item-parent-id'     => $this->parent_id,
            'menu-item-position'      => $this->position,
            'menu-item-type'          => $this->type,
            'menu-item-title'         => $this->title,
            'menu-item-url'           => $this->url,
            'menu-item-description'   => $this->description,
            'menu-item-attr-title'    => $this->attr_title,
            'menu-item-target'        => $this->target,
            'menu-item-classes'       => $this->classes,
            'menu-item-xfn'           => $this->xfn,
            'menu-item-status'        => $this->status,
        );
    }

    public function add_to_menu(MenuContainer $menu): int
    {
        $db_id = wp_update_nav_menu_item(
            $menu->get_id(),
            $this->db_id,
            $this->to_array()
        );

        if (is_wp_error($db_id)) {
            // return 0;
            throw new \Exception($db_id->get_error_message(), $db_id->get_error_code());
        }
        $this->db_id = $db_id;

        foreach ($this->children as $child) {
            $child->set_parent_id($db_id);
            $child->add_to_menu($menu);
        }
        return $db_id;
    }

    public function is_parent(): bool
    {
        return !empty($this->children);
    }
}
