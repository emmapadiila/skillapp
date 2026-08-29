<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationResponseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'evaluation_question_id' => ['required', 'integer', 'exists:evaluation_questions,id'],
            'question_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'likert_value' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
