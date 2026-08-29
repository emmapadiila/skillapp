<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin() ? true : null;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->belongsToCompany($company->id);
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isSuperadmin();
    }
}
