<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes;

use JsonSerializable;

class Response implements JsonSerializable
{
    private bool $success;
    private string $message;
    /** @var Response[] */
    private array $errors;
    private array $menus;

    public function __construct(bool $success = true, string $message = '')
    {
        $this->success = $success;
        $this->message = $message;
        $this->errors = [];
        $this->menus = [];
    }

    public function setError(string $message = ''): self
    {
        $this->success = false;
        return $this->setMessage($message);
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function addError(Response $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function addErrorArray(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMenus(): array
    {
        return $this->menus;
    }

    public function addMenus(array $menus): self
    {
        $this->menus = array_merge($this->menus, $menus);
        return $this;
    }

    public function jsonSerialize()
    {
        $array = array(
            'status' => $this->success ? "success" : "error",
            'message' => $this->message,
        );

        if ($this->hasErrors()) {
            $array['errors'] = $this->serializeErrors();
        }

        if (!empty($this->menus)) {
            $array['menus'] = $this->menus;
        }

        return $array;
    }

    private function serializeErrors(): array
    {
        $errors = [];
        foreach ($this->errors as $error) {
            $errors = $error->jsonSerialize();
        }
        return $errors;
    }
}
