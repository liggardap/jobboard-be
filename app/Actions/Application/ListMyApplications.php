<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\ApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyApplications extends BaseAction
{
    public function __construct(
        private readonly ApplicationServiceInterface $applicationService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->applicationService->listByUser($request->user()->id, $perPage);

        return PaginationResource::make($paginator, ApplicationResource::collection($paginator->getCollection()));
    }
}
