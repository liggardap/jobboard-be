<?php

namespace Tests\Feature\Job;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeleteJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_delete_own_job_and_returns_204(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create(['company_id' => $company->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    public function test_admin_can_delete_any_job(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $job = Job::factory()->create();

        $this->withToken(JWTAuth::fromUser($admin))
            ->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    public function test_company_cannot_delete_another_companys_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_delete_publishes_to_redis_delete_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel, $payload) => $channel === 'jobs:delete' && str_contains($payload, '"id"'));

        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create(['company_id' => $company->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson("/api/v1/jobs/{$job->id}");
    }

    public function test_delete_returns_404_for_missing_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson('/api/v1/jobs/999')
            ->assertStatus(404);
    }

    public function test_delete_requires_authentication(): void
    {
        $job = Job::factory()->create();

        $this->deleteJson("/api/v1/jobs/{$job->id}")
            ->assertStatus(401);
    }
}
