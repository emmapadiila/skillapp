<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationQuestion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationQuestion>
 */
class EvaluationQuestionFactory extends Factory
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
            'question_id' => Question::factory(),
            'display_order' => fake()->numberBetween(1, 200),
        ];
    }
}
