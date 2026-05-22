<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/jobs/{id}",
 *     summary="Get a single job",
 *     tags={"Jobs"},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Job",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Job")
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class GetJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $job = $this->jobService->getById($id);

        return BaseResponse::success(new JobResource($job));
    }
}
