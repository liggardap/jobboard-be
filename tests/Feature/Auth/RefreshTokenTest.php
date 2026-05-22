<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_returns_new_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['token', 'token_type', 'expires_in']])
            ->assertJson(['success' => true, 'data' => ['token_type' => 'bearer']]);

        $this->assertNotEquals($token, $response->json('data.token'));
    }

    public function test_refresh_without_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/refresh')->assertStatus(401);
    }
}
