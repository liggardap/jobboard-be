<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\CompanyResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
