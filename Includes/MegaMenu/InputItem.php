<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use stdClass;

class InputItem
{
    private object $data;
    public function __construct(array $input)
    {
        $this->data = (object)$input;
    }

    public function get_menu_item_name(): string
    {
        return $this->data->menu_item_name ?? '';
    }

    public function get_category_slug(): string
    {
        return $this->data->category_slug ?? '';
    }

    public function get_custom_url(): string
    {
        return $this->data->custom_url ?? '';
    }

    public function get_product_sku(): string
    {
        return $this->data->product_sku ?? '';
    }

    public function get_position(): int
    {
        return (int)$this->data->position ?? 0;
    }

    public function get_parent_menu_name(): string
    {
        return $this->data->parent_menu_item_name ?? '';
    }

    public function get_column_number(): int
    {
        return (int)$this->data->column_number ?? 0;
    }

    public function get_is_heading_text(): bool
    {
        return $this->data->heading_text ?? false;
    }

    public function get_column_widths(): ?array
    {
        $widths = (array)$this->data->column_widths;
        if (empty($widths)) return null;
        return $widths;
    }

    public function is_child(): bool
    {
        return empty($this->get_parent_menu_name());
    }

    public function is_category_item(): bool
    {
        return !empty($this->get_category_slug());
    }

    public function is_product_item(): bool
    {
        return !empty($this->get_product_sku());
    }

    public function is_custom_item(): bool
    {
        return !empty($this->get_custom_url());
    }
}
