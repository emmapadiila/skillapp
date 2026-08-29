<?php

namespace Database\Factories;

use App\Models\EvaluationResult;
use App\Models\Skill;
use App\Models\SkillResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillResult>
 */
class SkillResultFactory extends Factory
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
            'skill_id' => Skill::factory(),
            'score' => fake()->randomFloat(2, 1, 5),
            'level' => fake()->randomElement(['critical', 'developing', 'strong', 'outstanding']),
            'strengths' => [],
            'opportunities' => [],
        ];
    }
}
