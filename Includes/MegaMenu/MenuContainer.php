<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

class MenuContainer
{
    private string $name;
    private int $id;

    public function __construct(string $name, int $id)
    {
        $this->name = $name;
        $this->id = $id;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_id(): int
    {
        return $this->id;
    }
}
