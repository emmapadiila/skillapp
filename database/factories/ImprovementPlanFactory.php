<?php

namespace Database\Factories;

use App\Enums\ImprovementPlanStatus;
use App\Models\Company;
use App\Models\ImprovementPlan;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImprovementPlan>
 */
class ImprovementPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'skill_id' => Skill::factory(),
            'initial_level' => 'developing',
            'target_level' => 'strong',
            'progress' => 0,
            'status' => ImprovementPlanStatus::Pending,
            'start_date' => today(),
            'due_date' => today()->addMonth(),
            'reevaluation_date' => today()->addMonths(2),
        ];
    }
}
