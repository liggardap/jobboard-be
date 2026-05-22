<?php

namespace Tests\Integration\Repositories;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Repositories\JobRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsQueryPlan;

class JobRepositoryTest extends TestCase
{
    use AssertsQueryPlan;
    use RefreshDatabase;

    private JobRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new JobRepository;
    }

    public function test_find_by_id_returns_job_with_company(): void
    {
        $job = Job::factory()->create();

        $result = $this->repo->findById($job->id);

        $this->assertNotNull($result);
        $this->assertEquals($job->id, $result->id);
        $this->assertTrue($result->relationLoaded('company'));
    }

    public function test_find_by_id_returns_null_for_missing_job(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    public function test_find_by_company_id_returns_paginated_results(): void
    {
        $company = Company::factory()->create();
        Job::factory()->count(3)->create(['company_id' => $company->id]);

        $result = $this->repo->findByCompanyId($company->id);

        $this->assertEquals(3, $result->total());
    }

    public function test_find_by_company_id_uses_index(): void
    {
        Company::factory()->create();

        $this->assertNotFullTableScan(
            'jobs',
            'SELECT id, company_id, title, status FROM jobs WHERE company_id = ?',
            [1]
        );
    }

    public function test_chunk_active_uses_status_index(): void
    {
        $this->assertNotFullTableScan(
            'jobs',
            'SELECT id, title, status FROM jobs WHERE status = ? ORDER BY id ASC',
            [JobStatus::Active->value]
        );
    }

    public function test_create_persists_job_and_returns_with_db_defaults(): void
    {
        $company = Company::factory()->create();

        $job = $this->repo->create([
            'company_id' => $company->id,
            'title' => 'Test Role',
            'description' => 'A description',
            'category' => 'Engineering',
            'employment_type' => 'full_time',
            'location_country' => 'ID',
            'is_remote' => false,
            'currency' => 'IDR',
        ]);

        $this->assertDatabaseHas('jobs', ['title' => 'Test Role']);
        $this->assertInstanceOf(JobStatus::class, $job->status);
        $this->assertSame(JobStatus::Draft, $job->status);
    }

    public function test_update_persists_changes(): void
    {
        $job = Job::factory()->create(['title' => 'Old Title']);

        $updated = $this->repo->update($job, ['title' => 'New Title']);

        $this->assertEquals('New Title', $updated->title);
        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'title' => 'New Title']);
    }

    public function test_delete_removes_job_from_database(): void
    {
        $job = Job::factory()->create();

        $this->repo->delete($job);

        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    public function test_chunk_active_yields_only_active_jobs(): void
    {
        Job::factory()->active()->count(3)->create();
        Job::factory()->count(2)->create(['status' => JobStatus::Draft]);

        $collected = [];
        $this->repo->chunkActive(100, function ($jobs) use (&$collected) {
            foreach ($jobs as $job) {
                $collected[] = $job;
            }
        });

        $this->assertCount(3, $collected);
        foreach ($collected as $job) {
            $this->assertSame(JobStatus::Active, $job->status);
        }
    }
}
