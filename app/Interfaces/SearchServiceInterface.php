<?php

namespace App\Interfaces;

interface SearchServiceInterface
{
    public function searchJobs(array $params): array;
}
