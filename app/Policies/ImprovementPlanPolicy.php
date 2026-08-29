<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ImprovementPlan;
use App\Models\User;

class ImprovementPlanPolicy
{
    public function view(User $user, ImprovementPlan $plan): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->belongsToCompany($plan->company_id)
            && ($user->role === UserRole::HumanResources || $plan->user_id === $user->id);
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->company_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::HumanResources;
    }

    public function update(User $user, ImprovementPlan $plan): bool
    {
        return $user->role === UserRole::HumanResources
            && $user->belongsToCompany($plan->company_id);
    }

    public function delete(User $user, ImprovementPlan $plan): bool
    {
        return $this->update($user, $plan);
    }
}
