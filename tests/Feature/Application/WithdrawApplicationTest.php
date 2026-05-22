<?php

namespace Tests\Feature\Application;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WithdrawApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_withdraw_application_returns_204(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $application = Application::factory()->create(['user_id' => $user->id]);

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson("/api/v1/applications/{$application->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_non_owner_cannot_withdraw_returns_403(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Candidate]);
        $other = User::factory()->create(['role' => UserRole::Candidate]);
        $application = Application::factory()->create(['user_id' => $owner->id]);

        $this->withToken(JWTAuth::fromUser($other))
            ->deleteJson("/api/v1/applications/{$application->id}")
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_withdraw_nonexistent_application_returns_404(): void
    {
        $user = User::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->deleteJson('/api/v1/applications/999')
            ->assertStatus(404);
    }

    public function test_withdraw_requires_authentication(): void
    {
        $application = Application::factory()->create();

        $this->deleteJson("/api/v1/applications/{$application->id}")
            ->assertStatus(401);
    }
}
