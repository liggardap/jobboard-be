<?php

namespace App\Interfaces;

use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface
{
    public function findById(int $id): ?Company;

    public function findByUserId(int $userId): ?Company;

    public function create(array $data): Company;

    public function update(int $id, array $data): Company;

    public function delete(int $id): void;

    public function paginate(): LengthAwarePaginator;
}
