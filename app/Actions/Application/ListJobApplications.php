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

/**
 * @OA\Get(
 *     path="/jobs/{id}/applications",
 *     summary="List applications for a job (company owner only)",
 *     tags={"Applications"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
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
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
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
