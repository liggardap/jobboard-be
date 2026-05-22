<?php

namespace Tests\Feature\Application;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApplyToJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_apply_to_active_job_returns_201(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create();

        $response = $this->withToken(JWTAuth::fromUser($candidate))
            ->postJson("/api/v1/jobs/{$job->id}/apply", ['cover_letter' => 'I am interested.']);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'status', 'applied_at']]);

        $this->assertDatabaseHas('applications', ['user_id' => $candidate->id, 'job_id' => $job->id]);
    }

    public function test_apply_duplicate_returns_409(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create();
        Application::factory()->create(['user_id' => $candidate->id, 'job_id' => $job->id]);

        $this->withToken(JWTAuth::fromUser($candidate))
            ->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'conflict');
    }

    public function test_apply_to_inactive_job_returns_403(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->create();

        $this->withToken(JWTAuth::fromUser($candidate))
            ->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_company_user_cannot_apply_to_jobs(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $job = Job::factory()->active()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertStatus(403);
    }

    public function test_apply_to_nonexistent_job_returns_404(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);

        $this->withToken(JWTAuth::fromUser($candidate))
            ->postJson('/api/v1/jobs/999/apply')
            ->assertStatus(404);
    }

    public function test_cover_letter_exceeding_max_returns_422(): void
    {
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create();

        $this->withToken(JWTAuth::fromUser($candidate))
            ->postJson("/api/v1/jobs/{$job->id}/apply", ['cover_letter' => str_repeat('a', 5001)])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_apply_requires_authentication(): void
    {
        $job = Job::factory()->active()->create();

        $this->postJson("/api/v1/jobs/{$job->id}/apply")
            ->assertStatus(401);
    }
}
