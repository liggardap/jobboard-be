<?php

namespace Tests\Feature\Me;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_name_succeeds(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', ['name' => 'New Name']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_update_password_with_correct_current_password_succeeds(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_update_password_without_current_password_returns_422(): void
    {
        $user = User::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_update_password_with_wrong_current_password_returns_422(): void
    {
        $user = User::factory()->create(['password' => Hash::make('realpassword')]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_update_password_confirmation_mismatch_returns_422(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'different',
            ])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_update_company_user_includes_company_in_response(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/me', ['name' => 'Corp Owner']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Corp Owner')
            ->assertJsonStructure(['data' => ['company' => ['id', 'name']]]);
    }

    public function test_update_profile_requires_authentication(): void
    {
        $this->patchJson('/api/v1/me', ['name' => 'X'])
            ->assertStatus(401);
    }
}
