<?php

namespace App\Interfaces;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

interface JobServiceInterface
{
    public function getById(int $id): Job;

    public function listByCompany(int $companyId): LengthAwarePaginator;

    public function create(array $data): Job;

    public function update(int $id, array $data): Job;

    public function delete(int $id): void;
}
