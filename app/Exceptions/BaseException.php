<?php

namespace App\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    public function __construct(
        protected string $type,
        protected string $title,
        protected int $status,
        string $message = ''
    ) {
        parent::__construct($message ?: $title, $status);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
