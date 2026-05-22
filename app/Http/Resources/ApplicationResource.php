<?php

namespace App\Http\Resources;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Application */
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'cover_letter' => $this->cover_letter,
            'applied_at' => $this->applied_at->toIso8601ZuluString(),
            'job' => $this->whenLoaded('job', function () {
                /** @var Job $job */
                $job = $this->job;

                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company' => $job->relationLoaded('company') ? [
                        'id' => $job->company->id,
                        'name' => $job->company->name,
                    ] : null,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                /** @var User $user */
                $user = $this->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
        ];
    }
}
