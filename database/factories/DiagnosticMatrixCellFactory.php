<?php

namespace Database\Factories;

use App\Models\DiagnosticMatrixCell;
use App\Models\OrganizationalAxis;
use App\Models\OrganizationalDiagnostic;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosticMatrixCell>
 */
class DiagnosticMatrixCellFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizational_diagnostic_id' => OrganizationalDiagnostic::factory(),
            'organizational_axis_id' => OrganizationalAxis::factory(),
            'skill_category_id' => SkillCategory::factory(),
            'average_score' => fake()->randomFloat(2, 1, 5),
            'level' => fake()->randomElement(['critical', 'developing', 'strong', 'outstanding']),
            'evaluated_count' => fake()->numberBetween(1, 100),
        ];
    }
}
