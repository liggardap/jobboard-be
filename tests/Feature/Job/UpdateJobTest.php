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

class UpdateJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_update_own_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create(['company_id' => $company->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'Updated Title']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'title' => 'Updated Title']);
    }

    public function test_admin_can_update_any_job(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $job = Job::factory()->create();

        $this->withToken(JWTAuth::fromUser($admin))
            ->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'Admin Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Admin Updated');
    }

    public function test_company_cannot_update_another_companys_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'Hijacked'])
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_update_publishes_to_redis_index_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel) => $channel === 'jobs:index');

        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);
        $job = Job::factory()->create(['company_id' => $company->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'Updated']);
    }

    public function test_update_returns_404_for_missing_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/jobs/999', ['title' => 'X'])
            ->assertStatus(404);
    }

    public function test_update_requires_authentication(): void
    {
        $job = Job::factory()->create();

        $this->patchJson("/api/v1/jobs/{$job->id}", ['title' => 'X'])
            ->assertStatus(401);
    }
}
