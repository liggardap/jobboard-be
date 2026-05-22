<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Exceptions\ForbiddenException;
use App\Http\Resources\BaseResponse;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Delete(
 *     path="/jobs/{id}",
 *     summary="Delete a job posting",
 *     tags={"Jobs"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *     @OA\Response(response=204, description="Deleted"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class DeleteJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = $this->jobService->getById($id);

        if ($user->role !== UserRole::Admin && $job->company_id !== $user->company?->id) {
            throw new ForbiddenException('Cannot delete another company\'s job');
        }

        $this->jobService->delete($job);

        return BaseResponse::noContent();
    }
}
