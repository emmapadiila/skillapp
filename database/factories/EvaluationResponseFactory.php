<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationResponse>
 */
class EvaluationResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'evaluation_question_id' => EvaluationQuestion::factory(),
            'user_id' => User::factory(),
            'likert_value' => fake()->numberBetween(1, 5),
            'score' => fake()->numberBetween(1, 5),
            'answered_at' => now(),
        ];
    }
}
