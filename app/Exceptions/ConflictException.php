<?php

namespace App\Exceptions;

class ConflictException extends BaseException
{
    public function __construct(string $message = 'Resource conflict.')
    {
        parent::__construct(
            type: 'conflict',
            title: 'Conflict',
            status: 409,
            message: $message
        );
    }
}
