<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\ApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/me/applications",
 *     summary="List the authenticated user's applications",
 *     tags={"Applications"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated applications",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Application")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
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
