<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Http\Resources\JobResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListJobs extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->jobService->listByCompany($id, $perPage);

        return PaginationResource::make($paginator, JobResource::collection($paginator->getCollection()));
    }
}
