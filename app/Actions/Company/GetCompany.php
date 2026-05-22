<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/companies/{id}",
 *     summary="Get a company",
 *     tags={"Companies"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Company",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Company")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class GetCompany extends BaseAction
{
    public function __construct(
        private readonly CompanyServiceInterface $companyService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $company = $this->companyService->getById($id);

        return BaseResponse::success(new CompanyResource($company));
    }
}
