<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\ImprovementPlan;
use App\Models\RecommendedResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendedResource>
 */
class RecommendedResourceFactory extends Factory
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
            'type' => ResourceType::Reading,
            'title' => fake()->sentence(4),
            'url' => fake()->url(),
            'description' => fake()->sentence(),
            'recommended_by_ai' => false,
        ];
    }
}
