<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OrganizationalDiagnostic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationalDiagnostic>
 */
class OrganizationalDiagnosticFactory extends Factory
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
            'period_start' => today()->subMonth()->startOfMonth(),
            'period_end' => today()->subMonth()->endOfMonth(),
            'total_evaluated' => fake()->numberBetween(1, 100),
            'snapshot' => ['average_score' => fake()->randomFloat(2, 1, 5)],
            'closed_at' => now(),
        ];
    }
}
