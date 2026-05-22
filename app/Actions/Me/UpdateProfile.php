<?php

namespace App\Actions\Me;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Http\Requests\Me\UpdateProfileRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Patch(
 *     path="/me",
 *     summary="Update name or password",
 *     tags={"Me"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="current_password", type="string", description="Required when changing password"),
 *             @OA\Property(property="password", type="string", minLength=8),
 *             @OA\Property(property="password_confirmation", type="string")
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
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class UpdateProfile extends BaseAction
{
    public function handle(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if ($user->role === UserRole::Company) {
            $user->load('company');
        }

        return BaseResponse::success(new UserResource($user));
    }
}
