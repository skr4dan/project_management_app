<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Project',
            'description' => fake()->optional(0.8)->paragraph(),
            'status' => fake()->randomElement(['active', 'completed', 'archived']),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
