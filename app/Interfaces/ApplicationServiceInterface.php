<?php

namespace App\Interfaces;

use App\Models\Application;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicationServiceInterface
{
    public function getById(int $id): Application;

    public function listByJob(int $jobId): LengthAwarePaginator;

    public function listByUser(int $userId): LengthAwarePaginator;

    public function apply(int $jobId, int $userId): Application;

    public function delete(int $id): void;
}
