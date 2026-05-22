<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/companies",
 *     summary="List companies",
 *     tags={"Companies"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="filter[industry]", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="filter[country]", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated companies",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Company")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class ListCompanies extends BaseAction
{
    public function __construct(
        private readonly CompanyServiceInterface $companyService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->companyService->paginate($perPage);

        return PaginationResource::make($paginator, CompanyResource::collection($paginator->getCollection()));
    }
}
