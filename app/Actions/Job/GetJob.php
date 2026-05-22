<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $job = $this->jobService->getById($id);

        return BaseResponse::success(new JobResource($job));
    }
}
