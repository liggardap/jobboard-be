<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

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
