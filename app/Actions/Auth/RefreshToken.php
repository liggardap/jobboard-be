<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

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
