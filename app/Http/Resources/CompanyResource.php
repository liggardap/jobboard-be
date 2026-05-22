<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description ?? null,
            'industry' => $this->industry,
            'city' => $this->city ?? null,
            'country' => $this->country ?? null,
            'website' => $this->website ?? null,
            'is_verified' => $this->is_verified,
            'user' => $this->whenLoaded('user', function () {
                /** @var User $user */
                $user = $this->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
