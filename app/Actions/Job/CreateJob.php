<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Exceptions\ForbiddenException;
use App\Http\Requests\Job\CreateJobRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

class CreateJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(CreateJobRequest $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            throw new ForbiddenException('User does not have a company');
        }

        $job = $this->jobService->create($company, $request->validated());

        return BaseResponse::created(new JobResource($job));
    }
}
