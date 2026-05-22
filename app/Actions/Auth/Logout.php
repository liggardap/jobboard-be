<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

/**
 * @OA\Post(
 *     path="/auth/logout",
 *     summary="Invalidate the current JWT",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(response=204, description="Logged out"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class Logout extends BaseAction
{
    public function handle(Request $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return BaseResponse::noContent();
    }
}
