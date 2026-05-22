<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Exceptions\UnauthorizedException;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\BaseResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * @OA\Post(
 *     path="/auth/reset-password",
 *     summary="Reset password using the emailed token",
 *     tags={"Auth"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"token","email","password","password_confirmation"},
 *
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", minLength=8),
 *             @OA\Property(property="password_confirmation", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Password reset",
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
 *     @OA\Response(response=401, description="Invalid or expired token", @OA\JsonContent(ref="#/components/schemas/ProblemDetails")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
class ResetPassword extends BaseAction
{
    public function handle(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new UnauthorizedException('Invalid or expired reset token');
        }

        return BaseResponse::success(['message' => 'Password reset successfully.']);
    }
}
