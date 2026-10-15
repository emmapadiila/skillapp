<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EvaluationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::enum(EvaluationType::class)],
            'parent_evaluation_id' => ['nullable', 'integer', 'exists:evaluations,id'],
            'assessment_id' => ['nullable', 'integer', 'exists:assessments,id'],
            'question_count' => ['nullable', 'integer', 'between:1,200', 'required_without_all:question_ids,assessment_id'],
            'question_ids' => ['nullable', 'array', 'min:1', 'max:200', 'required_without_all:question_count,assessment_id'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
            'due_at' => ['required', 'date', 'after:now'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $provided = collect(['assessment_id', 'question_count', 'question_ids'])
                ->filter(fn (string $field): bool => $this->filled($field));

            if ($provided->count() > 1) {
                $validator->errors()->add(
                    'assessment_id',
                    'Indica una sola forma de seleccionar las preguntas: una prueba, un número de preguntas o una lista de preguntas.'
                );
            }
        });
    }
}
