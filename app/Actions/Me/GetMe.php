<?php

namespace App\Actions\Me;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetMe extends BaseAction
{
    public function handle(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Company) {
            $user->load('company');
        }

        return BaseResponse::success(new UserResource($user));
    }
}
