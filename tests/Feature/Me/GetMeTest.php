<?php

namespace Tests\Feature\Me;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class GetMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_me_returns_authenticated_user_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email', 'role', 'created_at']])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'candidate');
    }

    public function test_get_me_for_company_user_includes_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'company')
            ->assertJsonStructure(['data' => ['company' => ['id', 'name', 'industry']]]);
    }

    public function test_get_me_for_candidate_does_not_include_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonMissingPath('data.company');
    }

    public function test_get_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate');
    }
}
