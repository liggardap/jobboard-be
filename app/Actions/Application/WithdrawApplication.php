<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Interfaces\ApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawApplication extends BaseAction
{
    public function __construct(
        private readonly ApplicationServiceInterface $applicationService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $application = $this->applicationService->getById($id);
        $this->applicationService->withdraw($application, $request->user());

        return BaseResponse::noContent();
    }
}
