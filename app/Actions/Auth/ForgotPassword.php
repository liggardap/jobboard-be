<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends BaseAction
{
    public function handle(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->email]);

        return BaseResponse::success(['message' => 'If that email exists, a reset link has been sent.']);
    }
}
