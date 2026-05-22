<?php

namespace Tests\Unit\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\JobRepositoryInterface;
use App\Models\Company;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class JobServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_by_id_throws_not_found_for_missing_job(): void
    {
        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(99)->andReturn(null);

        $service = new JobService($repo);

        $this->expectException(NotFoundException::class);
        $service->getById(99);
    }

    public function test_get_by_id_returns_job_when_found(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('findById')->with($job->id)->andReturn($job);

        $service = new JobService($repo);
        $result = $service->getById($job->id);

        $this->assertInstanceOf(Job::class, $result);
        $this->assertEquals($job->id, $result->id);
    }

    public function test_create_publishes_to_redis_jobs_index_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel) => $channel === 'jobs:index');

        $company = Company::factory()->create();
        $job = Job::factory()->make(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('create')->once()->andReturn($job);

        $service = new JobService($repo);
        $service->create($company, ['title' => 'Dev Role', 'category' => 'Engineering']);
    }

    public function test_create_handles_redis_failure_gracefully(): void
    {
        Redis::shouldReceive('publish')->andThrow(new \Exception('Redis down'));

        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('create')->once()->andReturn($job);

        $service = new JobService($repo);
        $result = $service->create($company, ['title' => 'Dev Role', 'category' => 'Engineering']);

        $this->assertInstanceOf(Job::class, $result);
    }

    public function test_update_publishes_to_redis_jobs_index_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel) => $channel === 'jobs:index');

        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('update')->once()->with($job, ['title' => 'New Title'])->andReturn($job);

        $service = new JobService($repo);
        $service->update($job, ['title' => 'New Title']);
    }

    public function test_delete_publishes_to_redis_jobs_delete_channel(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(fn ($channel, $payload) => $channel === 'jobs:delete' && str_contains($payload, '"id"'));

        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('delete')->once()->with($job);

        $service = new JobService($repo);
        $service->delete($job);
    }

    public function test_list_by_company_delegates_to_repository(): void
    {
        $company = Company::factory()->create();
        $paginator = Job::factory()->count(2)->create(['company_id' => $company->id])
            ->toQuery()->paginate(15);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('findByCompanyId')->with($company->id, 15)->andReturn($paginator);

        $service = new JobService($repo);
        $result = $service->listByCompany($company->id, 15);

        $this->assertEquals($paginator, $result);
    }

    public function test_delete_handles_redis_failure_gracefully(): void
    {
        Redis::shouldReceive('publish')->andThrow(new \Exception('Redis down'));

        $company = Company::factory()->create();
        $job = Job::factory()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(JobRepositoryInterface::class);
        $repo->shouldReceive('delete')->once()->with($job);

        $service = new JobService($repo);
        $service->delete($job);

        $this->assertTrue(true);
    }
}
