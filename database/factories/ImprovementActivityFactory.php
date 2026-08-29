<?php

namespace Database\Factories;

use App\Enums\ImprovementActivityStatus;
use App\Models\ImprovementActivity;
use App\Models\ImprovementPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImprovementActivity>
 */
class ImprovementActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'improvement_plan_id' => ImprovementPlan::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(15, 120),
            'status' => ImprovementActivityStatus::Pending,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
