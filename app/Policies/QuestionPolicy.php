<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin() ? true : null;
    }

    public function view(User $user, Question $question): bool
    {
        return $user->role === UserRole::HumanResources && $user->belongsToCompany($question->company_id);
    }

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::HumanResources;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::HumanResources;
    }

    public function update(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }
}
