<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Role>
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->words(2, true),
            'permissions' => fake()->randomElements([
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
                'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            ], fake()->numberBetween(1, 5)),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the role is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'admin',
            'name' => 'Administrator',
            'permissions' => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
                'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                'system.admin',
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Create a manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'manager',
            'name' => 'Manager',
            'permissions' => [
                'projects.view', 'projects.create', 'projects.edit',
                'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                'users.view',
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Create a user role.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'user',
            'name' => 'User',
            'permissions' => [
                'tasks.view_assigned', 'tasks.update_status',
                'projects.view_own',
                'profile.edit',
            ],
            'is_active' => true,
        ]);
    }
}
