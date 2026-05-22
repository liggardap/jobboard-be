<?php

namespace App\Repositories;

use App\Interfaces\ApplicationRepositoryInterface;
use App\Models\Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class ApplicationRepository implements ApplicationRepositoryInterface
{
    private const COLUMNS = ['id', 'job_id', 'user_id', 'cover_letter', 'status', 'applied_at', 'updated_at'];

    public function findById(int $id): ?Application
    {
        return Application::select(self::COLUMNS)
            ->with(['job:id,title,company_id', 'job.company:id,name'])
            ->find($id);
    }

    public function findByUserAndJob(int $userId, int $jobId): ?Application
    {
        return Application::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->first();
    }

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(Application::class)
            ->select(self::COLUMNS)
            ->where('user_id', $userId)
            ->allowedFilters('status')
            ->allowedSorts('applied_at', 'status')
            ->with(['job:id,title,company_id', 'job.company:id,name'])
            ->paginate($perPage);
    }

    public function findByJobId(int $jobId, int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(Application::class)
            ->select(self::COLUMNS)
            ->where('job_id', $jobId)
            ->allowedFilters('status')
            ->allowedSorts('applied_at', 'status')
            ->with('user:id,name,email')
            ->paginate($perPage);
    }

    public function create(array $data): Application
    {
        return Application::create($data)->fresh();
    }

    public function delete(Application $application): void
    {
        $application->delete();
    }
}
