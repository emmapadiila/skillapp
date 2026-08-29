<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Company;
use App\Models\OrganizationalAxis;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
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
            'area_id' => Area::factory(),
            'organizational_axis_id' => OrganizationalAxis::factory(),
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
