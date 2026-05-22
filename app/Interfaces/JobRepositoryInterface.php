<?php

namespace App\Interfaces;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

interface JobRepositoryInterface
{
    public function findById(int $id): ?Job;

    public function findByCompanyId(int $companyId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Job;

    public function update(Job $job, array $data): Job;

    public function delete(Job $job): void;

    public function chunkActive(int $size, callable $callback): void;
}
