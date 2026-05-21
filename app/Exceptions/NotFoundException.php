<?php

namespace App\Exceptions;

class NotFoundException extends BaseException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct(
            type: 'not_found',
            title: 'Not Found',
            status: 404,
            message: $message
        );
    }
}
