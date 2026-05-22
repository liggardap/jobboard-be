<?php

namespace Tests\Feature\Job;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ListJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_company_jobs(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $company = Company::factory()->create();
        Job::factory()->count(3)->create(['company_id' => $company->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/companies/{$company->id}/jobs");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']]);
    }

    public function test_list_jobs_requires_authentication(): void
    {
        $company = Company::factory()->create();

        $this->getJson("/api/v1/companies/{$company->id}/jobs")
            ->assertStatus(401);
    }

    public function test_list_jobs_only_returns_jobs_for_requested_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $other = Company::factory()->create();

        Job::factory()->count(2)->create(['company_id' => $company->id]);
        Job::factory()->count(3)->create(['company_id' => $other->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/companies/{$company->id}/jobs");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_list_jobs_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        Job::factory()->active()->create(['company_id' => $company->id]);
        Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Draft]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/companies/{$company->id}/jobs?filter[status]=active");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_list_jobs_is_paginated(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        Job::factory()->count(5)->create(['company_id' => $company->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/companies/{$company->id}/jobs?per_page=2");

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }
}
