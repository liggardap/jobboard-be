<?php

namespace Tests\Unit\Services;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Interfaces\ApplicationRepositoryInterface;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_by_id_throws_not_found_for_missing_application(): void
    {
        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(99)->andReturn(null);

        $service = new ApplicationService($repo);

        $this->expectException(NotFoundException::class);
        $service->getById(99);
    }

    public function test_apply_throws_forbidden_when_user_is_not_candidate(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company]);
        $job = Job::factory()->active()->create(['company_id' => $company->id]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $service = new ApplicationService($repo);

        $this->expectException(ForbiddenException::class);
        $service->apply($user, $job, null);
    }

    public function test_apply_throws_forbidden_when_job_is_not_active(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Draft]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $service = new ApplicationService($repo);

        $this->expectException(ForbiddenException::class);
        $service->apply($user, $job, null);
    }

    public function test_apply_throws_conflict_on_duplicate_application(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create(['company_id' => $company->id]);
        $existing = Application::factory()->create(['user_id' => $user->id, 'job_id' => $job->id]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $repo->shouldReceive('findByUserAndJob')->with($user->id, $job->id)->andReturn($existing);

        $service = new ApplicationService($repo);

        $this->expectException(ConflictException::class);
        $service->apply($user, $job, null);
    }

    public function test_apply_creates_application_for_valid_candidate(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $job = Job::factory()->active()->create(['company_id' => $company->id]);
        $application = Application::factory()->make(['user_id' => $user->id, 'job_id' => $job->id]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $repo->shouldReceive('findByUserAndJob')->with($user->id, $job->id)->andReturn(null);
        $repo->shouldReceive('create')->once()->andReturn($application);

        $service = new ApplicationService($repo);
        $result = $service->apply($user, $job, 'My cover letter');

        $this->assertInstanceOf(Application::class, $result);
    }

    public function test_withdraw_throws_forbidden_when_non_owner_withdraws(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Candidate]);
        $other = User::factory()->create(['role' => UserRole::Candidate]);
        $application = Application::factory()->create(['user_id' => $owner->id]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $service = new ApplicationService($repo);

        $this->expectException(ForbiddenException::class);
        $service->withdraw($application, $other);
    }

    public function test_withdraw_deletes_application_for_owner(): void
    {
        $user = User::factory()->create(['role' => UserRole::Candidate]);
        $application = Application::factory()->create(['user_id' => $user->id]);

        $repo = Mockery::mock(ApplicationRepositoryInterface::class);
        $repo->shouldReceive('delete')->once()->with($application);

        $service = new ApplicationService($repo);
        $service->withdraw($application, $user);

        $this->assertTrue(true);
    }
}
