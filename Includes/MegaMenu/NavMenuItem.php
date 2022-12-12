<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class NavMenuItem
{
    private int $db_id = 0;
    private int $object_id = 0;
    private string $object = '';
    private int $parent_id = 0;
    private int $position = 0;

    private string $type = 'custom';
    private string $title;
    private string $url = '';
    private string $description = '';
    private string $attr_title = '';
    private string $target = '';
    private string $classes = '';
    private string $xfn = '';
    private string $status = '';

    private string $post_date = '';
    private string $post_date_gmt = '';

    public function __construct(string $title)
    {
        $this->title = $title;
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

    #endregion
    public function to_array(): array
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
            'menu-item-post-date'     => $this->post_date,
            'menu-item-post-date-gmt' => $this->post_date_gmt,
        );
    }
}
