<?php

namespace App\Interfaces;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

interface JobServiceInterface
{
    public function getById(int $id): Job;

    public function listByCompany(int $companyId, int $perPage = 15): LengthAwarePaginator;

    public function create(Company $company, array $data): Job;

    public function update(Job $job, array $data): Job;

    public function delete(Job $job): void;
}
