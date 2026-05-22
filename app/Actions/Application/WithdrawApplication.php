<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Interfaces\ApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Delete(
 *     path="/applications/{id}",
 *     summary="Withdraw an application",
 *     tags={"Applications"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\Response(response=204, description="Withdrawn"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
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
