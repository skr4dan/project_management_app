<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => \App\Models\Role::factory(),
            'status' => fake()->randomElement(['active', 'inactive', 'blocked']),
            'avatar' => fake()->optional(0.3)->imageUrl(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'remember_token' => Str::random(10),
        ];
    }

    private function withRole(string $role): callable
    {
        return fn (array $attributes) => [
            'role_id' => Role::bySlug($role)->first()->id,
        ];
    }

    public function admin(): static
    {
        return $this->state($this->withRole('admin'));
    }

    public function manager(): static
    {
        return $this->state($this->withRole('manager'));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
