<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    public function test_register_creates_user_and_returns_201_with_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->valid);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token', 'token_type', 'expires_in'],
            ])
            ->assertJson(['success' => true, 'data' => ['token_type' => 'bearer']]);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }

    public function test_register_assigns_candidate_role_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid)->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'role' => UserRole::Candidate->value,
        ]);
    }

    public function test_register_with_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->valid);

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_register_with_mismatched_password_confirmation_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->valid, [
            'password_confirmation' => 'different',
        ]));

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }

    public function test_register_with_short_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->valid, [
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ]));

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }
}
