<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Requests\Company\CreateCompanyRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;

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
