<?php

namespace App\Http\Requests\Job;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'max:100'],
            'employment_type' => ['sometimes', new Enum(EmploymentType::class)],
            'location_city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'location_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'is_remote' => ['sometimes', 'nullable', 'boolean'],
            'salary_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'salary_max' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'status' => ['sometimes', new Enum(JobStatus::class)],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
