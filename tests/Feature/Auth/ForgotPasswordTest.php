<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_with_existing_email_returns_200(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'alice@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'alice@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.message', 'If that email exists, a reset link has been sent.');
    }

    public function test_forgot_password_with_nonexistent_email_still_returns_200(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'If that email exists, a reset link has been sent.');
    }

    public function test_forgot_password_with_missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }

    public function test_forgot_password_with_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJsonPath('type', 'validation_error');
    }
}
