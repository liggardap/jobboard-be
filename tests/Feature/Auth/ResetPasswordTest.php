<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_with_valid_token_returns_200(): void
    {
        $user = User::factory()->create(['email' => 'alice@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'alice@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.message', 'Password reset successfully.');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_reset_password_with_invalid_token_returns_401(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'alice@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('type', 'unauthorized');
    }

    public function test_reset_password_with_missing_token_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'alice@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }

    public function test_reset_password_with_mismatched_confirmation_returns_422(): void
    {
        $user = User::factory()->create(['email' => 'alice@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'alice@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }
}
