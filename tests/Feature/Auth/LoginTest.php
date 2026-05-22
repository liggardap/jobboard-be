<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_200_with_token(): void
    {
        User::factory()->create(['email' => 'bob@example.com', 'password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bob@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token', 'token_type', 'expires_in'],
            ])
            ->assertJson(['success' => true, 'data' => ['token_type' => 'bearer']]);
    }

    public function test_login_with_wrong_password_returns_401_with_www_authenticate(): void
    {
        User::factory()->create(['email' => 'bob@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bob@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'unauthorized')
            ->assertJsonPath('status', 401);
    }

    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_login_with_missing_fields_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', ['email' => 'bob@example.com']);

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }
}
