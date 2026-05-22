<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RequireRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:api', 'role:company'])->get('/test-company-only', fn () => response()->json(['ok' => true]));
    }

    public function test_company_user_can_access_company_only_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $token = JWTAuth::fromUser($user);

        $this->withToken($token)->getJson('/test-company-only')->assertStatus(200);
    }

    public function test_candidate_user_is_blocked_from_company_only_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/test-company-only');

        $response->assertStatus(403)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'forbidden')
            ->assertJsonPath('status', 403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/test-company-only')->assertStatus(401);
    }
}
