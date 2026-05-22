<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_token_and_returns_204(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(204);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_subsequent_request_after_logout_returns_401(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(204);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(401);
    }
}
