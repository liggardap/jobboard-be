<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ListCompaniesTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function test_returns_paginated_list_of_companies(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(3)->create();

        $response = $this->withToken($this->token($user))->getJson('/api/v1/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/companies')->assertStatus(401);
    }

    public function test_filters_by_industry(): void
    {
        $user = User::factory()->create();
        Company::factory()->create(['industry' => 'Technology']);
        Company::factory()->create(['industry' => 'Finance']);

        $response = $this->withToken($this->token($user))
            ->getJson('/api/v1/companies?filter[industry]=Technology');

        $response->assertStatus(200)->assertJsonPath('meta.total', 1);
    }

    public function test_sorts_by_created_at_descending(): void
    {
        $user = User::factory()->create();
        $old = Company::factory()->create(['created_at' => now()->subDays(5)]);
        $new = Company::factory()->create(['created_at' => now()]);

        $response = $this->withToken($this->token($user))
            ->getJson('/api/v1/companies?sort=-created_at');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
    }
}
