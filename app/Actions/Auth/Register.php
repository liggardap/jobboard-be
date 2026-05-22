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

/**
 * @OA\Post(
 *     path="/auth/register",
 *     summary="Register a new candidate account",
 *     tags={"Auth"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"name","email","password","password_confirmation"},
 *
 *             @OA\Property(property="name", type="string", example="Alice Smith"),
 *             @OA\Property(property="email", type="string", format="email", example="alice@example.com"),
 *             @OA\Property(property="password", type="string", minLength=8, example="secret123"),
 *             @OA\Property(property="password_confirmation", type="string", example="secret123")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Registered",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", ref="#/components/schemas/User"),
 *                 @OA\Property(property="token", type="string"),
 *                 @OA\Property(property="token_type", type="string", example="bearer"),
 *                 @OA\Property(property="expires_in", type="integer", example=3600)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ProblemDetails"))
 * )
 */
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
