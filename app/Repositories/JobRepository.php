<?php

namespace App\Repositories;

use App\Enums\JobStatus;
use App\Interfaces\JobRepositoryInterface;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class JobRepository implements JobRepositoryInterface
{
    private const COLUMNS = [
        'id', 'company_id', 'title', 'description', 'category', 'employment_type',
        'location_city', 'location_country', 'is_remote', 'salary_min', 'salary_max',
        'currency', 'status', 'published_at', 'expires_at', 'created_at', 'updated_at',
    ];

    public function findById(int $id): ?Job
    {
        return Job::select(self::COLUMNS)
            ->with('company:id,name,industry')
            ->find($id);
    }

    public function findByCompanyId(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Job::class)
            ->select(self::COLUMNS)
            ->where('company_id', $companyId)
            ->allowedFilters('status', 'category')
            ->allowedSorts('created_at', 'published_at', 'title');

        return $query->paginate($perPage);
    }

    public function create(array $data): Job
    {
        return Job::create($data)->fresh();
    }

    public function update(Job $job, array $data): Job
    {
        $job->update($data);

        return $job->fresh();
    }

    public function delete(Job $job): void
    {
        $job->delete();
    }

    public function chunkActive(int $size, callable $callback): void
    {
        Job::select(self::COLUMNS)
            ->with('company:id,name,industry')
            ->where('status', JobStatus::Active)
            ->chunkById($size, $callback);
    }

    public function chunkUpdatedSince(Carbon $since, int $size, callable $callback): void
    {
        Job::select(self::COLUMNS)
            ->with('company:id,name,industry')
            ->where('status', JobStatus::Active)
            ->where('updated_at', '>=', $since)
            ->chunkById($size, $callback);
    }
}
