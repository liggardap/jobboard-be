<?php

namespace App\Exceptions;

class UnauthorizedException extends BaseException
{
    public function __construct(string $message = 'Unauthenticated')
    {
        parent::__construct('unauthorized', 'Unauthorized', 401, $message);
    }
}
