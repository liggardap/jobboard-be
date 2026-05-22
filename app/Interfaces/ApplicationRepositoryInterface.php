<?php

namespace App\Interfaces;

use App\Models\Application;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicationRepositoryInterface
{
    public function findById(int $id): ?Application;

    public function findByUserAndJob(int $userId, int $jobId): ?Application;

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function findByJobId(int $jobId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Application;

    public function delete(Application $application): void;
}
