<?php

namespace Tests\Integration\Repositories;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Repositories\ApplicationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsQueryPlan;

class ApplicationRepositoryTest extends TestCase
{
    use AssertsQueryPlan;
    use RefreshDatabase;

    private ApplicationRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ApplicationRepository;
    }

    public function test_find_by_id_returns_application_with_relations(): void
    {
        $application = Application::factory()->create();

        $result = $this->repo->findById($application->id);

        $this->assertNotNull($result);
        $this->assertEquals($application->id, $result->id);
        $this->assertTrue($result->relationLoaded('job'));
    }

    public function test_find_by_id_returns_null_for_missing_application(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    public function test_find_by_user_and_job_returns_existing_application(): void
    {
        $application = Application::factory()->create();

        $result = $this->repo->findByUserAndJob($application->user_id, $application->job_id);

        $this->assertNotNull($result);
        $this->assertEquals($application->id, $result->id);
    }

    public function test_find_by_user_and_job_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repo->findByUserAndJob(999, 999));
    }

    public function test_find_by_user_id_uses_index(): void
    {
        $this->assertNotFullTableScan(
            'applications',
            'SELECT id, user_id, status FROM applications WHERE user_id = ?',
            [1]
        );
    }

    public function test_find_by_job_id_uses_index(): void
    {
        $this->assertNotFullTableScan(
            'applications',
            'SELECT id, job_id, status FROM applications WHERE job_id = ?',
            [1]
        );
    }

    public function test_find_by_user_and_job_uses_unique_index(): void
    {
        $this->assertNotFullTableScan(
            'applications',
            'SELECT id FROM applications WHERE user_id = ? AND job_id = ?',
            [1, 1]
        );
    }

    public function test_find_by_user_id_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $jobs = Job::factory()->active()->count(3)->create(['company_id' => $company->id]);

        foreach ($jobs as $job) {
            Application::factory()->create(['user_id' => $user->id, 'job_id' => $job->id]);
        }

        $result = $this->repo->findByUserId($user->id);

        $this->assertEquals(3, $result->total());
    }

    public function test_find_by_job_id_returns_paginated_results(): void
    {
        $job = Job::factory()->active()->create();
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            Application::factory()->create(['user_id' => $user->id, 'job_id' => $job->id]);
        }

        $result = $this->repo->findByJobId($job->id);

        $this->assertEquals(2, $result->total());
    }

    public function test_create_persists_application_with_db_defaults(): void
    {
        $user = User::factory()->create();
        $job = Job::factory()->active()->create();

        $application = $this->repo->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'cover_letter' => 'I am excited to apply.',
        ]);

        $this->assertDatabaseHas('applications', ['user_id' => $user->id, 'job_id' => $job->id]);
        $this->assertNotNull($application->applied_at);
        $this->assertNotNull($application->status);
    }

    public function test_delete_removes_application(): void
    {
        $application = Application::factory()->create();

        $this->repo->delete($application);

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }
}
