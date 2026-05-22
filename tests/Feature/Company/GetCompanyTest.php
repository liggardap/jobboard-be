<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class GetCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_single_company_with_user_relation(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson("/api/v1/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'industry', 'user']])
            ->assertJsonPath('data.id', $company->id)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_returns_404_for_nonexistent_company(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))
            ->getJson('/api/v1/companies/999');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'not_found');
    }

    public function test_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/companies/1')->assertStatus(401);
    }
}
