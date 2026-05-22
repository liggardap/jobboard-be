<?php

namespace App\Actions\Company;

use App\Actions\BaseAction;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\PaginationResource;
use App\Interfaces\CompanyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
