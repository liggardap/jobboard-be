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
