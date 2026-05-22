<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_verified_is_cast_to_boolean(): void
    {
        $company = Company::factory()->create(['is_verified' => false]);

        $this->assertFalse($company->fresh()->is_verified);
    }

    public function test_belongs_to_user_relationship(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $company->user());
    }

    public function test_has_many_jobs_relationship(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->jobs());
    }

    public function test_deleting_company_cascades_to_jobs(): void
    {
        $company = Company::factory()->create();
        Job::factory()->count(3)->create(['company_id' => $company->id]);

        $this->assertDatabaseCount('jobs', 3);

        $company->delete();

        $this->assertDatabaseCount('jobs', 0);
    }
}
