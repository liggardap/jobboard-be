<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_cast_to_user_role_enum(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);

        $this->assertInstanceOf(UserRole::class, $user->fresh()->role);
        $this->assertSame(UserRole::Candidate, $user->fresh()->role);
    }

    public function test_get_jwt_identifier_returns_primary_key(): void
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    public function test_get_jwt_custom_claims_returns_role_array(): void
    {
        $user = User::factory()->create(['role' => UserRole::Company]);

        $claims = $user->getJWTCustomClaims();

        $this->assertArrayHasKey('roles', $claims);
        $this->assertEquals(['company'], $claims['roles']);
    }

    public function test_has_one_company_relationship(): void
    {
        $user = User::factory()->company()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->company());
    }

    public function test_has_many_applications_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->applications());
    }
}
