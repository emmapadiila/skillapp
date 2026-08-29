<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if (! $user->belongsToCompany($evaluation->company_id)) {
            return false;
        }

        return $user->role === UserRole::HumanResources || $evaluation->user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->company_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::HumanResources;
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $evaluation->user_id === $user->id && $this->view($user, $evaluation);
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return $user->role === UserRole::HumanResources
            && $user->belongsToCompany($evaluation->company_id);
    }
}
