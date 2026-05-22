<?php

namespace App\Interfaces;

use App\Models\Company;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyServiceInterface
{
    public function getById(int $id): Company;

    public function create(User $user, array $data): Company;

    public function update(Company $company, User $user, array $data): Company;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
