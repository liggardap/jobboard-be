<?php

namespace App\Interfaces;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicationServiceInterface
{
    public function getById(int $id): Application;

    public function apply(User $user, Job $job, ?string $coverLetter): Application;

    public function listByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function listByJob(int $jobId, int $perPage = 15): LengthAwarePaginator;

    public function withdraw(Application $application, User $user): void;
}
