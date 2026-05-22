<?php

namespace Tests\Feature\Application;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ListMyApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_own_applications(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        Application::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me/applications');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_only_returns_authenticated_users_applications(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $other = User::factory()->create(['role' => UserRole::Candidate]);

        Application::factory()->count(2)->create(['user_id' => $user->id]);
        Application::factory()->count(3)->create(['user_id' => $other->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me/applications');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_filter_by_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        Application::factory()->create(['user_id' => $user->id, 'status' => ApplicationStatus::Pending]);
        Application::factory()->reviewed()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me/applications?filter[status]=pending');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_is_paginated(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        Application::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me/applications?per_page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/me/applications')->assertStatus(401);
    }
}
