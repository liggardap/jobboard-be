<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'email_verified_at' => $this->email_verified_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'company' => $this->when(
                $this->role === UserRole::Company && $this->relationLoaded('company'),
                function () {
                    /** @var Company $company */
                    $company = $this->company;

                    return new CompanyResource($company);
                }
            ),
        ];
    }
}
