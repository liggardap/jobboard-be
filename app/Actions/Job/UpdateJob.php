<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Exceptions\ForbiddenException;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Patch(
 *     path="/jobs/{id}",
 *     summary="Update a job posting",
 *     tags={"Jobs"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="status", type="string", enum={"draft","active","closed"})
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
 *             @OA\Property(property="data", ref="#/components/schemas/Job")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class UpdateJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(UpdateJobRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        if ($user->role !== UserRole::Admin && $job->company_id !== $user->company?->id) {
            throw new ForbiddenException('Cannot update another company\'s job');
        }

        $updated = $this->jobService->update($job, $request->validated());

        return BaseResponse::success(new JobResource($updated));
    }
}
