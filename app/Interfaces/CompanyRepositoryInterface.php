<?php

namespace App\Interfaces;

use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface
{
    public function findById(int $id): ?Company;

    public function create(array $data): Company;

    public function update(Company $company, array $data): Company;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
