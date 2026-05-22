<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Interfaces\CompanyRepositoryInterface;
use App\Interfaces\CompanyServiceInterface;
use App\Models\Company;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyService implements CompanyServiceInterface
{
    public function __construct(
        private readonly CompanyRepositoryInterface $repository,
    ) {}

    public function getById(int $id): Company
    {
        $company = $this->repository->findById($id);

        if (! $company) {
            throw new NotFoundException('Company not found');
        }

        return $company;
    }

    public function create(User $user, array $data): Company
    {
        if ($user->company()->exists()) {
            throw new ConflictException('User already has a company');
        }

        return $this->repository->create([...$data, 'user_id' => $user->id]);
    }

    public function update(Company $company, User $user, array $data): Company
    {
        if ($company->user_id !== $user->id && $user->role !== UserRole::Admin) {
            throw new ForbiddenException('Cannot update another company');
        }

        return $this->repository->update($company, $data);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
