<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = \App\Models\Role::where('slug', 'admin')->first()->id;
        $manager = \App\Models\Role::where('slug', 'manager')->first()->id;
        $user = \App\Models\Role::where('slug', 'user')->first()->id;

        // Roles are now created by migration, create test users with different roles
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role_id' => $admin,
        ]);

        User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'User',
            'email' => 'manager@example.com',
            'role_id' => $manager,
        ]);

        User::factory()->create([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'role_id' => $user,
        ]);

        // User::factory(10)->create();
    }
}
