<?php

namespace Tests\Feature\Application;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ListJobApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_list_applications_for_own_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->active()->create(['company_id' => $company->id]);
        Application::factory()->count(3)->create(['job_id' => $job->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/jobs/{$job->id}/applications");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_company_cannot_list_applications_for_another_companys_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->active()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_candidate_cannot_list_job_applications(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create();

        $this->withToken(JWTAuth::fromUser($candidate))
            ->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertStatus(403);
    }

    public function test_can_filter_applications_by_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->active()->create(['company_id' => $company->id]);

        Application::factory()->create(['job_id' => $job->id, 'status' => ApplicationStatus::Pending]);
        Application::factory()->reviewed()->create(['job_id' => $job->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/jobs/{$job->id}/applications?filter[status]=reviewed");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_returns_404_for_nonexistent_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/jobs/999/applications')
            ->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $job = Job::factory()->active()->create();

        $this->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertStatus(401);
    }
}
