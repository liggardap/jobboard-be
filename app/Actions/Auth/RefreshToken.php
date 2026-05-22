<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

/**
 * @OA\Post(
 *     path="/auth/refresh",
 *     summary="Refresh the JWT and return a new token",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Token refreshed",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token", type="string"),
 *                 @OA\Property(property="token_type", type="string", example="bearer"),
 *                 @OA\Property(property="expires_in", type="integer")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class RefreshToken extends BaseAction
{
    public function handle(Request $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->refresh();

        return BaseResponse::success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
