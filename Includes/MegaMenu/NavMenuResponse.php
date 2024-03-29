<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use PelemanProductUploader\Includes\MegaMenu\Response;

class NavMenuResponse extends Response
{
    private int $item;

    public function __construct(bool $success = true, string $message = '', int $item = 0)
    {
        parent::__construct($success, $message);
        $this->item = $item;
    }
    public function get_item(): int
    {
        return $this->item;
    }

    public function to_array()
    {
        $array = parent::to_array();
        $array['item'] = $this->item;
        return $array;
    }
}
