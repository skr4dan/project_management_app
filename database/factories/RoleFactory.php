<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $slug = \Illuminate\Support\Str::slug($name);

        return [
            'slug' => $slug,
            'name' => ucwords($name),
            'permissions' => $this->generateRandomPermissions(),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**w
     * Generate a random set of permissions for the role.
     *
     * @return array<string>
     */
    private function generateRandomPermissions(): array
    {
        $allPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'tasks.assign', 'reports.view', 'profile.edit',
        ];

        // Randomly select 3-8 permissions
        $selectedPermissions = fake()->randomElements(
            $allPermissions,
            fake()->numberBetween(3, 8)
        );

        return array_unique($selectedPermissions);
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'slug' => 'admin',
                'name' => 'Administrator',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
                    'projects.manage_all',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                    'tasks.assign', 'tasks.manage_all',
                    'system.admin',
                ],
                'is_active' => true,
            ];
        });
    }

    /**
     * Create a manager role.
     */
    public function manager(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'slug' => 'manager',
                'name' => 'Manager',
                'permissions' => [
                    'projects.view', 'projects.create', 'projects.edit_own',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                    'tasks.assign', 'tasks.manage_own_projects',
                    'users.view', 'reports.view',
                ],
                'is_active' => true,
            ];
        });
    }

    /**
     * Create a user role.
     */
    public function user(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'slug' => 'user',
                'name' => 'User',
                'permissions' => [
                    'tasks.view_assigned', 'tasks.update_status',
                    'projects.view_own', 'profile.edit',
                ],
                'is_active' => true,
            ];
        });
    }

    /**
     * Create an inactive role.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
