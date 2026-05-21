<?php

namespace App\Interfaces;

use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyServiceInterface
{
    public function getById(int $id): Company;

    public function getByUserId(int $userId): Company;

    public function create(array $data): Company;

    public function update(int $id, array $data): Company;

    public function delete(int $id): void;

    public function paginate(): LengthAwarePaginator;
}
