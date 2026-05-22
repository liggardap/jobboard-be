<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Exceptions\UnauthorizedException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

class Login extends BaseAction
{
    public function handle(LoginRequest $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $token = $guard->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (! $token) {
            throw new UnauthorizedException('Invalid credentials');
        }

        return BaseResponse::success([
            'user' => new UserResource($guard->user()),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
