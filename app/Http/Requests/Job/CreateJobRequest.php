<?php

namespace App\Http\Requests\Job;

use App\Enums\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'employment_type' => ['required', new Enum(EmploymentType::class)],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'size:2'],
            'is_remote' => ['nullable', 'boolean'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
