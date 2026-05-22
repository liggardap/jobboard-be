<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Patch(
 *     path="/companies/{id}",
 *     summary="Update a company profile",
 *     tags={"Companies"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string", example="Acme Corp"),
 *             @OA\Property(property="description", type="string", example="A leading technology company"),
 *             @OA\Property(property="industry", type="string", example="Technology"),
 *             @OA\Property(property="city", type="string", example="Jakarta"),
 *             @OA\Property(property="country", type="string", example="ID"),
 *             @OA\Property(property="website", type="string", example="https://acme.com")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Updated",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Company")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class UpdateCompany extends BaseAction
{
    public function __construct(
        private readonly CompanyServiceInterface $companyService,
    ) {}

    public function handle(UpdateCompanyRequest $request, int $id): JsonResponse
    {
        $company = $this->companyService->getById($id);

        $updated = $this->companyService->update(
            $company,
            $request->user(),
            $request->validated(),
        );

        return BaseResponse::success(new CompanyResource($updated));
    }
}
