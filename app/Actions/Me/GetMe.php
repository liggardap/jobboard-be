<?php

namespace App\Actions\Me;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/me",
 *     summary="Get the authenticated user's profile",
 *     tags={"Me"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Profile",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class GetMe extends BaseAction
{
    public function handle(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Company) {
            $user->load('company');
        }

        return BaseResponse::success(new UserResource($user));
    }
}
