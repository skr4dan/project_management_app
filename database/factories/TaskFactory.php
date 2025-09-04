<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'project_id' => \App\Models\Project::factory(),
            'assigned_to' => \App\Models\User::factory(),
            'created_by' => \App\Models\User::factory(),
            'due_date' => fake()->optional(0.8)->dateTimeBetween('now', '+1 year'),
        ];
    }
}
