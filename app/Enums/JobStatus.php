<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
