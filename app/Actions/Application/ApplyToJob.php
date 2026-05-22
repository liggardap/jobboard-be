<?php

namespace App\Actions\Application;

use App\Actions\BaseAction;
use App\Http\Requests\Application\ApplyToJobRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\BaseResponse;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *     path="/jobs/{id}/apply",
 *     summary="Apply to a job (candidates only)",
 *     tags={"Applications"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="cover_letter", type="string", nullable=true, example="I am excited to apply for this role...")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Applied",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Application")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Job not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=409, description="Already applied", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class ApplyToJob extends BaseAction
{
    public function __construct(
        private readonly ApplicationServiceInterface $applicationService,
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(ApplyToJobRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        $application = $this->applicationService->apply(
            $user,
            $job,
            $request->validated('cover_letter'),
        );

        return BaseResponse::created(new ApplicationResource($application));
    }
}
