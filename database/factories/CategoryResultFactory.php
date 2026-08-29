<?php

namespace Database\Factories;

use App\Models\CategoryResult;
use App\Models\EvaluationResult;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryResult>
 */
class CategoryResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_result_id' => EvaluationResult::factory(),
            'skill_category_id' => SkillCategory::factory(),
            'score' => fake()->randomFloat(2, 1, 5),
            'level' => fake()->randomElement(['critical', 'developing', 'strong', 'outstanding']),
            'strengths' => [],
            'opportunities' => [],
        ];
    }
}
