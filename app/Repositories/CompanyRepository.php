<?php

namespace App\Repositories;

use App\Interfaces\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly Company $model,
    ) {}

    public function findById(int $id): ?Company
    {
        return $this->model
            ->select(['id', 'user_id', 'name', 'description', 'industry', 'city', 'country', 'website', 'is_verified', 'created_at', 'updated_at'])
            ->with('user:id,name,email')
            ->find($id);
    }

    public function create(array $data): Company
    {
        return $this->model->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Company::class)
            ->select(['id', 'user_id', 'name', 'industry', 'city', 'country', 'is_verified', 'created_at'])
            ->allowedFilters('industry', 'country', 'is_verified')
            ->allowedSorts('name', 'created_at')
            ->with('user:id,name');

        return $query->paginate($perPage);
    }
}
