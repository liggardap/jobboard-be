<?php

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Interfaces\CompanyRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_by_id_throws_not_found_for_missing_company(): void
    {
        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(99)->andReturn(null);

        $service = new CompanyService($repo);

        $this->expectException(NotFoundException::class);
        $service->getById(99);
    }

    public function test_create_throws_conflict_if_user_already_has_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $service = new CompanyService($repo);

        $this->expectException(ConflictException::class);
        $service->create($user, ['name' => 'Dupe Corp', 'description' => 'test', 'industry' => 'Tech']);
    }

    public function test_update_throws_forbidden_when_non_owner_updates(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Company]);
        $other = User::factory()->create(['role' => UserRole::Candidate]);
        $company = Company::factory()->create(['user_id' => $owner->id]);

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $service = new CompanyService($repo);

        $this->expectException(ForbiddenException::class);
        $service->update($company, $other, ['name' => 'Hijacked']);
    }

    public function test_create_creates_company_for_user_without_one(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['name'] === 'Acme Corp' && $data['user_id'] === $user->id)
            ->andReturn(Company::factory()->make(['user_id' => $user->id]));

        $service = new CompanyService($repo);
        $result = $service->create($user, ['name' => 'Acme Corp', 'description' => 'test', 'industry' => 'Tech']);

        $this->assertInstanceOf(Company::class, $result);
    }

    public function test_paginate_delegates_to_repository(): void
    {
        $paginator = Company::factory()->count(2)->create()
            ->toQuery()->paginate(15);

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('paginate')->with(15)->andReturn($paginator);

        $service = new CompanyService($repo);
        $result = $service->paginate(15);

        $this->assertEquals($paginator, $result);
    }

    public function test_get_by_id_returns_company_when_found(): void
    {
        $company = Company::factory()->create();

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('findById')->with($company->id)->andReturn($company);

        $service = new CompanyService($repo);
        $result = $service->getById($company->id);

        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals($company->id, $result->id);
    }

    public function test_update_allows_admin_to_update_any_company(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Company]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $company = Company::factory()->create(['user_id' => $owner->id]);

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('update')->once()->with($company, ['name' => 'Admin Fix'])->andReturn($company);

        $service = new CompanyService($repo);
        $result = $service->update($company, $admin, ['name' => 'Admin Fix']);

        $this->assertInstanceOf(Company::class, $result);
    }
}
