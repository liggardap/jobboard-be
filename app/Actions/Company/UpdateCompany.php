<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;

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
