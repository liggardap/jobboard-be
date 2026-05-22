<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Exceptions\ForbiddenException;
use App\Http\Requests\Job\CreateJobRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\JobResource;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *     path="/jobs",
 *     summary="Create a job posting",
 *     tags={"Jobs"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"title","description","category","employment_type","location_country","currency"},
 *
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="category", type="string"),
 *             @OA\Property(property="employment_type", type="string", enum={"full_time","part_time","contract","internship"}),
 *             @OA\Property(property="location_city", type="string"),
 *             @OA\Property(property="location_country", type="string"),
 *             @OA\Property(property="is_remote", type="boolean"),
 *             @OA\Property(property="salary_min", type="integer"),
 *             @OA\Property(property="salary_max", type="integer"),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="status", type="string", enum={"draft","active","closed"})
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Created",
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
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class CreateJob extends BaseAction
{
    public function __construct(
        private readonly JobServiceInterface $jobService,
    ) {}

    public function handle(CreateJobRequest $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            throw new ForbiddenException('User does not have a company');
        }

        $job = $this->jobService->create($company, $request->validated());

        return BaseResponse::created(new JobResource($job));
    }
}
