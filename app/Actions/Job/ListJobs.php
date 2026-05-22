<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Http\Resources\JobResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/companies/{id}/jobs",
 *     summary="List jobs for a company",
 *     tags={"Jobs"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="filter[status]", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated jobs",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Job")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
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
