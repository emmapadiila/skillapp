<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'skill_id' => ['required', 'integer', 'exists:skills,id'],
            'organizational_axis_id' => ['nullable', 'integer', 'exists:organizational_axes,id'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'difficulty' => ['required', Rule::enum(QuestionDifficulty::class)],
            'text' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array', 'max:10'],
            'options.*.label' => ['required_with:options', 'string', 'distinct', 'max:10'],
            'options.*.text' => ['required_with:options', 'string', 'max:2000'],
            'options.*.score' => ['required_with:options', 'numeric', 'between:0,5'],
            'options.*.display_order' => ['required_with:options', 'integer', 'distinct', 'min:1', 'max:100'],
        ];
    }
}
