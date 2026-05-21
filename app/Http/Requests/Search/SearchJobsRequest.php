<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'array'],
            'filter.category' => ['nullable', 'string', 'max:100'],
            'filter.location' => ['nullable', 'string', 'max:100'],
            'filter.employment_type' => ['nullable', 'string', 'in:full_time,part_time,contract,internship'],
            'filter.salary_min' => ['nullable', 'integer', 'min:0'],
            'filter.is_remote' => ['nullable', 'string', 'in:true,false'],
            'sort' => ['nullable', 'string', 'in:published_at,-published_at,salary_min,-salary_min'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
