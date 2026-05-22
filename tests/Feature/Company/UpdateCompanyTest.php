<?php

namespace Tests\Feature\Company;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'Updated Corp']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Corp');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Updated Corp']);
    }

    public function test_non_owner_gets_403(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Company]);
        $other = User::factory()->create(['role' => UserRole::Company]);
        $company = Company::factory()->create(['user_id' => $owner->id]);

        $this->withToken(JWTAuth::fromUser($other))
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'Hijacked'])
            ->assertStatus(403)
            ->assertJsonPath('type', 'forbidden');
    }

    public function test_admin_can_update_any_company(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Company]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $company = Company::factory()->create(['user_id' => $owner->id]);

        $this->withToken(JWTAuth::fromUser($admin))
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'Admin Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Admin Updated');
    }

    public function test_returns_404_for_nonexistent_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson('/api/v1/companies/999', ['name' => 'X'])
            ->assertStatus(404);
    }

    public function test_candidate_cannot_access_update_endpoint(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $company = Company::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))
            ->patchJson("/api/v1/companies/{$company->id}", ['name' => 'X'])
            ->assertStatus(403);
    }
}
