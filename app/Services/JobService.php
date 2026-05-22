<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\JobRepositoryInterface;
use App\Interfaces\JobServiceInterface;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;

class JobService implements JobServiceInterface
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    public function getById(int $id): Job
    {
        $job = $this->repository->findById($id);

        if (! $job) {
            throw new NotFoundException('Job not found');
        }

        return $job;
    }

    public function listByCompany(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findByCompanyId($companyId, $perPage);
    }

    public function create(Company $company, array $data): Job
    {
        $job = $this->repository->create([...$data, 'company_id' => $company->id]);
        $job->load('company:id,name,industry');
        $this->publishToIndex($job);

        return $job;
    }

    public function update(Job $job, array $data): Job
    {
        $job = $this->repository->update($job, $data);
        $job->load('company:id,name,industry');
        $this->publishToIndex($job);

        return $job;
    }

    public function delete(Job $job): void
    {
        $jobId = $job->id;
        $this->repository->delete($job);
        $this->publishToDelete($jobId);
    }

    private function publishToIndex(Job $job): void
    {
        try {
            Redis::publish('jobs:index', json_encode($job->toSearchArray()));
        } catch (\Throwable) {
            // Redis unavailable — job saved to MySQL, ES sync deferred (Phase 1 behaviour)
        }
    }

    private function publishToDelete(int $jobId): void
    {
        try {
            Redis::publish('jobs:delete', json_encode(['id' => $jobId]));
        } catch (\Throwable) {
            // Redis unavailable — handled gracefully
        }
    }
}
