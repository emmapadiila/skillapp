<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ImprovementPlanStatus;
use App\Enums\ResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImprovementPlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'skill_id' => ['required', 'integer', 'exists:skills,id'],
            'initial_level' => ['required', 'string', 'max:50'],
            'target_level' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', Rule::enum(ImprovementPlanStatus::class)],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reevaluation_date' => ['required', 'date', 'after:start_date'],
            'ai_recommendation' => ['nullable', 'string', 'max:10000'],
            'activities' => ['nullable', 'array', 'max:100'],
            'activities.*.title' => ['required_with:activities', 'string', 'max:255'],
            'activities.*.description' => ['required_with:activities', 'string', 'max:5000'],
            'activities.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'activities.*.display_order' => ['required_with:activities', 'integer', 'distinct', 'min:1'],
            'resources' => ['nullable', 'array', 'max:100'],
            'resources.*.type' => ['required_with:resources', Rule::enum(ResourceType::class)],
            'resources.*.title' => ['required_with:resources', 'string', 'max:255'],
            'resources.*.url' => ['nullable', 'url:https', 'max:2048'],
            'resources.*.description' => ['nullable', 'string', 'max:5000'],
            'resources.*.recommended_by_ai' => ['sometimes', 'boolean'],
        ];
    }
}
