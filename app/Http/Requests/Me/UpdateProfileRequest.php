<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'current_password' => ['required_with:password', 'current_password:api'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }
}
