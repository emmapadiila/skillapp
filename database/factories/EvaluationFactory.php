<?php

namespace Database\Factories;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
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
            'type' => EvaluationType::Initial,
            'status' => EvaluationStatus::Pending,
            'question_count' => 0,
            'assigned_at' => now(),
            'due_at' => now()->addWeek(),
        ];
    }
}
