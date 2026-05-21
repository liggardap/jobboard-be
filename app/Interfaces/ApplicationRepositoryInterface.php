<?php

namespace App\Interfaces;

use App\Models\Application;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicationRepositoryInterface
{
    public function findById(int $id): ?Application;

    public function findByJobId(int $jobId): LengthAwarePaginator;

    public function findByUserId(int $userId): LengthAwarePaginator;

    public function existsByJobAndUser(int $jobId, int $userId): bool;

    public function create(array $data): Application;

    public function delete(int $id): void;
}
