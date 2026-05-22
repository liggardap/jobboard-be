<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

/**
 * @OA\Post(
 *     path="/auth/forgot-password",
 *     summary="Send a password reset link",
 *     tags={"Auth"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"email"},
 *
 *             @OA\Property(property="email", type="string", format="email")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Reset link sent (response is identical whether email exists or not)",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="message", type="string")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class ForgotPassword extends BaseAction
{
    public function handle(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->email]);

        return BaseResponse::success(['message' => 'If that email exists, a reset link has been sent.']);
    }
}
