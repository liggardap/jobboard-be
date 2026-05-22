<?php

namespace App\Actions\Me;

use App\Actions\BaseAction;
use App\Enums\UserRole;
use App\Http\Requests\Me\UpdateProfileRequest;
use App\Http\Resources\BaseResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class UpdateProfile extends BaseAction
{
    public function handle(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if ($user->role === UserRole::Company) {
            $user->load('company');
        }

        return BaseResponse::success(new UserResource($user));
    }
}
