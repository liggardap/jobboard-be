<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Requests\Application\ApplyToJobRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\BaseResponse;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

class ApplyToJob extends BaseAction
{
    public function __construct(
        private readonly ApplicationServiceInterface $applicationService,
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(ApplyToJobRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        $application = $this->applicationService->apply(
            $user,
            $job,
            $request->validated('cover_letter'),
        );

        return BaseResponse::created(new ApplicationResource($application));
    }
}
