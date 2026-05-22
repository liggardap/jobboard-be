<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Exceptions\ForbiddenException;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

class UpdateJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(UpdateJobRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        if ($user->role !== UserRole::Admin && $job->company_id !== $user->company?->id) {
            throw new ForbiddenException('Cannot update another company\'s job');
        }

        $updated = $this->jobService->update($job, $request->validated());

        return BaseResponse::success(new JobResource($updated));
    }
}
