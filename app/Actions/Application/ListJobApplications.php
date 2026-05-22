<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Exceptions\ForbiddenException;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListJobApplications extends BaseAction
{
    public function __construct(
        private readonly ApplicationServiceInterface $applicationService,
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        if ($job->company_id !== $user->company?->id) {
            throw new ForbiddenException('Cannot view applications for another company\'s job');
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->applicationService->listByJob($id, $perPage);

        return PaginationResource::make($paginator, ApplicationResource::collection($paginator->getCollection()));
    }
}
