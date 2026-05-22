<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

class Register extends BaseAction
{
    public function handle(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => UserRole::Candidate,
        ]);

        /** @var JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return BaseResponse::created([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
