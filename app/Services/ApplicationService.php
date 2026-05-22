<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Interfaces\ApplicationRepositoryInterface;
use App\Interfaces\ApplicationServiceInterface;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ApplicationService implements ApplicationServiceInterface
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $repository,
    ) {}

    public function getById(int $id): Application
    {
        $application = $this->repository->findById($id);

        if (! $application) {
            throw new NotFoundException('Application not found');
        }

        return $application;
    }

    public function apply(User $user, Job $job, ?string $coverLetter): Application
    {
        if ($user->role !== UserRole::Candidate) {
            throw new ForbiddenException('Only candidates can apply to jobs');
        }

        if ($job->status !== JobStatus::Active) {
            throw new ForbiddenException('Cannot apply to an inactive job');
        }

        if ($this->repository->findByUserAndJob($user->id, $job->id)) {
            throw new ConflictException('Already applied to this job');
        }

        return $this->repository->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'cover_letter' => $coverLetter,
        ]);
    }

    public function listByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findByUserId($userId, $perPage);
    }

    public function listByJob(int $jobId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findByJobId($jobId, $perPage);
    }

    public function withdraw(Application $application, User $user): void
    {
        if ($application->user_id !== $user->id) {
            throw new ForbiddenException('Cannot withdraw another user\'s application');
        }

        $this->repository->delete($application);
    }
}
