<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Requests\Company\CreateCompanyRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *     path="/companies",
 *     summary="Create a company profile",
 *     tags={"Companies"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"name","description","industry"},
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
 *         response=201,
 *         description="Created",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Company")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=409, description="Company already exists", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class CreateCompany extends BaseAction
{
    public function __construct(
        private readonly CompanyServiceInterface $companyService,
    ) {}

    public function handle(CreateCompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->create(
            $request->user(),
            $request->validated(),
        );

        return BaseResponse::created(new CompanyResource($company));
    }
}
