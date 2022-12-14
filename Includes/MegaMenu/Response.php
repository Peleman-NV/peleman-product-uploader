<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use JsonSerializable;

class Response
{
    private bool $success;
    private string $message;
    private array $responses;

    private int $code;

    /**
     * API response container
     *
     * @param boolean $success if the api action was successful
     * @param string $message message to show
     * @param integer $code HTTP code of hte response
     */
    public function __construct(bool $success = true, string $message = '', int $code = 200)
    {
        $this->success = $success;
        $this->message = $message;
        $this->code = 200;
    }

    public function setError(string $message = '', int $code = 400): self
    {
        $this->success = false;
        return $this->setMessage($message);
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function addErrorArray(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function to_array()
    {
        $array = array(
            'status' => $this->success ? "success" : "error",
            'message' => $this->message,
        );

        return $array;
    }
}
