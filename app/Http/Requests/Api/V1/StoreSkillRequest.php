<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'skill_category_id' => ['required', 'integer', 'exists:skill_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_level' => ['nullable', 'integer', 'between:1,5'],
            'is_active' => ['sometimes', 'boolean'],
            'axis_ids' => ['required', 'array', 'min:1', 'max:10'],
            'axis_ids.*' => ['integer', 'distinct', 'exists:organizational_axes,id'],
        ];
    }
}
