<?php

namespace Database\Factories;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Company;
use App\Models\Question;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'skill_id' => Skill::factory(),
            'type' => QuestionType::SelfReport,
            'difficulty' => QuestionDifficulty::Basic,
            'text' => fake()->sentence().'?',
            'is_active' => true,
        ];
    }
}
