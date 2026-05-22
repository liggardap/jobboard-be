<?php

namespace Tests\Feature\Company;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Acme Corp',
        'description' => 'A great company',
        'industry' => 'Technology',
        'city' => 'Jakarta',
        'country' => 'ID',
        'website' => 'https://acme.example.com',
    ];

    public function test_company_user_can_create_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/companies', $this->valid);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'industry']])
            ->assertJsonPath('data.name', 'Acme Corp');

        $this->assertDatabaseHas('companies', ['name' => 'Acme Corp', 'user_id' => $user->id]);
    }

    public function test_candidate_cannot_create_company(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/companies', $this->valid)
            ->assertStatus(403);
    }

    public function test_user_with_existing_company_gets_409(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);
        Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/companies', $this->valid);

        $response->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'conflict');
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $this->withToken(JWTAuth::fromUser($user))
            ->postJson('/api/v1/companies', [])
            ->assertStatus(422)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_returns_401_without_token(): void
    {
        $this->postJson('/api/v1/companies', $this->valid)->assertStatus(401);
    }
}
