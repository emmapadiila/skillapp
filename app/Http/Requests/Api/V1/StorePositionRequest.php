<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'organizational_axis_id' => ['required', 'integer', 'exists:organizational_axes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'skills' => ['nullable', 'array', 'max:100'],
            'skills.*.id' => ['required_with:skills', 'integer', 'distinct', 'exists:skills,id'],
            'skills.*.weight' => ['required_with:skills', 'numeric', 'between:0.01,100'],
            'skills.*.required_level' => ['required_with:skills', 'integer', 'between:1,5'],
        ];
    }
}
