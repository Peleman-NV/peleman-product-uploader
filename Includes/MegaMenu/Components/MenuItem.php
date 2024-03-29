<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu\Components;

use PelemanProductUploader\Includes\MegaMenu\InputItem;
use PelemanProductUploader\Includes\MegaMenu\MenuContainer;

abstract class MenuItem
{
    #region properties
    private string $parent_title;

    protected int $db_id;
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

    protected ?InputItem $input;
    protected array $css;

    /** @var MenuItem[] */
    protected array $children;
    #endregion

    public function __construct(
        string $title,
        string $attr_title,
        int $position,
        string $parent = '',
        string $status = 'publish'
    ) {
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

        $this->input = null;
        $this->children = [];
        $this->css = [];
    }

    public abstract static function create_new(InputItem $input): self;

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

    public function get_column_number(): int
    {
        return $this->input->get_column_number() ?? 0;
    }

    public function add_input(InputItem $item): void
    {
        $this->input = $item;
    }

    public function add_css_classes(string ...$args): self
    {
        $this->css = array_merge($this->css, $args);
        return $this;
    }

    public function get_position(): int
    {
        return $this->position;
    }

    public function get_db_id(): int
    {
        return $this->db_id;
    }

    public function get_product_sku(): string
    {
        return $this->input->get_product_sku();
    }

    public function get_menu_item_name(): string
    {
        return $this->title;
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

    public function is_parent(): bool
    {
        return !empty($this->children);
    }

    /**
     * wrapper method to set this menuitem as the child of a WP menu, 
     *
     * @param MenuContainer $menu
     * @return void
     */
    public function add_to_menu(MenuContainer $menu): void
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

        error_log($this->get_menu_item_name() . ": " . $db_id);

        foreach ($this->children as $child) {
            $child->set_parent_id($db_id);
            $child->add_to_menu($menu);
        }
    }


    final public function register_settings(): void
    {
        $this->add_settings_to_post_meta();

        foreach ($this->children as $child) {
            $child->register_settings();
        }
    }

    public function add_settings_to_post_meta(): void
    {
        $this->update_post_meta('_megamenu', $this->generate_settings());
        $this->update_post_meta('_menu_item_classes', $this->css);

        $this->update_post_meta('_menu_item_megamenu_col', 'columns-2');
        $this->update_post_meta('_menu_item_megamenu_col_tab', 'columns-1');
        $this->update_post_meta('_menu_item_megamenu_icon_alignment', 'left');
        $this->update_post_meta('_menu_item_megamenu_icon_size', 13);
        $this->update_post_meta('_menu_item_megamenu_style', 'menu_style_column');
        $this->update_post_meta('_menu_item_megamenu_widgetarea', 0);
        $this->update_post_meta('_menu_item_megamenu_background_image', '');
        $this->update_post_meta('_menu_item_megamenu_icon', '');
        $this->update_post_meta('_menu_item_megamenu_icon_color', '');
        $this->update_post_meta('_menu_item_megamenu_sublabel', '');
        $this->update_post_meta('_menu_item_megamenu_sublabel_color', '');
    }

    final protected function update_post_meta(string $key, $value): bool
    {
        if (empty($this->db_id)) return false;
        return !empty(update_post_meta($this->db_id, $key, $value));
    }

    protected abstract function generate_settings(): array;
}
