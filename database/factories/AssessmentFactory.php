<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\OrganizationalAxis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(3),
            'name' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'organizational_axis_id' => OrganizationalAxis::factory(),
            'is_active' => true,
        ];
    }
}
