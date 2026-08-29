<?php

namespace Database\Factories;

use App\Enums\GapPriority;
use App\Models\Area;
use App\Models\Company;
use App\Models\OrganizationalAxis;
use App\Models\OrganizationalDiagnostic;
use App\Models\OrganizationalGap;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationalGap>
 */
class OrganizationalGapFactory extends Factory
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
            'company_id' => Company::factory(),
            'skill_id' => Skill::factory(),
            'organizational_axis_id' => OrganizationalAxis::factory(),
            'area_id' => Area::factory(),
            'priority' => GapPriority::Medium,
            'affected_count' => fake()->numberBetween(1, 50),
            'score_gap' => fake()->randomFloat(2, 0.1, 4),
            'status' => 'open',
        ];
    }
}
