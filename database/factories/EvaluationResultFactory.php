<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationResult>
 */
class EvaluationResultFactory extends Factory
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
            'total_score' => fake()->randomFloat(2, 1, 5),
            'level' => fake()->randomElement(['critical', 'developing', 'strong', 'outstanding']),
            'strengths' => [],
            'opportunities' => [],
            'calculated_at' => now(),
            'immutable_at' => now(),
        ];
    }
}
