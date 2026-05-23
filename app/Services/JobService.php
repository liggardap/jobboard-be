<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\JobRepositoryInterface;
use App\Interfaces\JobServiceInterface;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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
            $subscribers = $this->redisPublish('jobs:index', json_encode($job->toSearchArray()));
            Log::info('Redis publish jobs:index', ['job_id' => $job->id, 'title' => $job->title, 'subscribers' => $subscribers]);
        } catch (\Throwable $e) {
            Log::error('Redis publish failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }

    private function publishToDelete(int $jobId): void
    {
        try {
            $subscribers = $this->redisPublish('jobs:delete', json_encode(['id' => $jobId]));
            Log::info('Redis publish jobs:delete', ['job_id' => $jobId, 'subscribers' => $subscribers]);
        } catch (\Throwable $e) {
            Log::error('Redis publish failed', ['job_id' => $jobId, 'error' => $e->getMessage()]);
        }
    }

    private function redisPublish(string $channel, string $message): int
    {
        $host = (string) config('database.redis.default.host', 'redis');
        $port = (int) config('database.redis.default.port', 6379);

        /** @var \Redis $client */
        $client = new \Redis();
        $client->connect($host, $port);

        /** @var int $result */
        $result = $client->publish($channel, $message);

        return $result;
    }
}
