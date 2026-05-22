<?php

namespace Tests\Feature\Job;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreateJobTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'title' => 'Senior PHP Developer',
        'description' => 'We are looking for an experienced PHP developer.',
        'category' => 'Engineering',
        'employment_type' => 'full_time',
        'location_city' => 'Jakarta',
        'location_country' => 'ID',
        'is_remote' => false,
        'salary_min' => 10_000_000,
        'salary_max' => 20_000_000,
        'currency' => 'IDR',
    ];

    public function test_company_user_can_create_job_and_returns_201(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', $this->valid);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'title', 'status', 'company']])
            ->assertJsonPath('data.title', 'Senior PHP Developer');
    }

    public function test_create_job_persists_in_database(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', $this->valid);

        $this->assertDatabaseHas('jobs', ['title' => 'Senior PHP Developer']);
    }

    public function test_create_job_publishes_to_redis_index_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel, $payload) => $channel === 'jobs:index' && str_contains($payload, 'Senior PHP Developer'));

        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', $this->valid);
    }

    public function test_candidate_cannot_create_job(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', $this->valid)
            ->assertStatus(403);
    }

    public function test_company_user_without_company_profile_gets_403(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', $this->valid)
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', [])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_invalid_employment_type_returns_422(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/jobs', [...$this->valid, 'employment_type' => 'freelance'])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_create_job_requires_authentication(): void
    {
        $this->postJson('/api/v1/jobs', $this->valid)->assertStatus(401);
    }
}
