<?php

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    public function __construct(string $message = 'Access denied.')
    {
        parent::__construct(
            type: 'forbidden',
            title: 'Forbidden',
            status: 403,
            message: $message
        );
    }
}
