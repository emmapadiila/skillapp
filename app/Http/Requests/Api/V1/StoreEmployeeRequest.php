<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($employee)],
            'role' => ['required', Rule::enum(UserRole::class)->only([UserRole::HumanResources, UserRole::Collaborator])],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
