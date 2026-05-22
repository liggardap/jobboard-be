<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Job */
class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'employment_type' => $this->employment_type->value,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'is_remote' => $this->is_remote,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'expires_at' => $this->expires_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'company' => $this->whenLoaded('company', function () {
                /** @var Company $company */
                $company = $this->company;

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'industry' => $company->industry,
                ];
            }),
        ];
    }
}
