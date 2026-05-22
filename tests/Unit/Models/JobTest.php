<?php

namespace Tests\Unit\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_cast_to_job_status_enum(): void
    {
        $job = Job::factory()->create(['status' => JobStatus::Draft]);

        $this->assertInstanceOf(JobStatus::class, $job->fresh()->status);
        $this->assertSame(JobStatus::Draft, $job->fresh()->status);
    }

    public function test_employment_type_is_cast_to_employment_type_enum(): void
    {
        $job = Job::factory()->create(['employment_type' => EmploymentType::FullTime]);

        $this->assertInstanceOf(EmploymentType::class, $job->fresh()->employment_type);
        $this->assertSame(EmploymentType::FullTime, $job->fresh()->employment_type);
    }

    public function test_is_remote_is_cast_to_boolean(): void
    {
        $job = Job::factory()->create(['is_remote' => true]);

        $this->assertTrue($job->fresh()->is_remote);
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $job = Job::factory()->active()->create();

        $this->assertInstanceOf(Carbon::class, $job->fresh()->published_at);
    }

    public function test_belongs_to_company_relationship(): void
    {
        $job = Job::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $job->company());
    }

    public function test_has_many_applications_relationship(): void
    {
        $job = Job::factory()->create();

        $this->assertInstanceOf(HasMany::class, $job->applications());
    }

    public function test_to_search_array_returns_correct_structure(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Corp', 'industry' => 'Technology']);
        $job = Job::factory()->active()->create([
            'company_id' => $company->id,
            'title' => 'Backend Engineer',
            'employment_type' => EmploymentType::FullTime,
            'status' => JobStatus::Active,
            'is_remote' => true,
        ]);
        $job->load('company');

        $array = $job->toSearchArray();

        $this->assertSame($job->id, $array['id']);
        $this->assertSame('Backend Engineer', $array['title']);
        $this->assertSame('full_time', $array['employment_type']);
        $this->assertSame('active', $array['status']);
        $this->assertTrue($array['is_remote']);
        $this->assertSame('Acme Corp', $array['company']['name']);
        $this->assertSame('Technology', $array['company']['industry']);
        $this->assertNotNull($array['published_at']);
    }
}
