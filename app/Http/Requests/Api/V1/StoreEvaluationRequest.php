<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EvaluationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::enum(EvaluationType::class)],
            'parent_evaluation_id' => ['nullable', 'integer', 'exists:evaluations,id'],
            'question_count' => ['nullable', 'required_without:question_ids', 'prohibited_with:question_ids', 'integer', 'between:1,200'],
            'question_ids' => ['nullable', 'required_without:question_count', 'prohibited_with:question_count', 'array', 'min:1', 'max:200'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
            'due_at' => ['required', 'date', 'after:now'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
