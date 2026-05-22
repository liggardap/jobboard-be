<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
}
