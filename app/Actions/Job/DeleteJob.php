<?php

namespace App\Actions\Job;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Exceptions\ForbiddenException;
use App\Http\Resources\BaseResponse;
use App\Interfaces\JobServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
