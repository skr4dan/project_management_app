<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
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
        $adminRoleId = \App\Models\Role::where('slug', 'admin')->first()->id;
        $managerRoleId = \App\Models\Role::where('slug', 'manager')->first()->id;
        $userRoleId = \App\Models\Role::where('slug', 'user')->first()->id;

        // Roles are now created by migration, create test users with different roles
        User::factory()->state([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role_id' => $adminRoleId,
        ])->create();

        User::factory()->count(5)->state(fn () => [
            'first_name' => 'Manager',
            'last_name' => 'User',
            'email' => 'manager_'.uniqid().'@example.com',
            'role_id' => $managerRoleId,
        ])->create();

        User::factory()->count(5)->state(fn () => [
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'user_'.uniqid().'@example.com',
            'role_id' => $userRoleId,
        ])->create();

        $users = User::all();

        $projects = Project::factory()
            ->state(fn ($attributes) => [
                'created_by' => $users->random()->id,
            ])
            ->count(3)
            ->create();

        $tasks = Task::factory()
            ->count(20)
            ->state(fn ($attributes) => [
                'project_id' => $projects->random()->id,
                'assigned_to' => $users->random()->id,
                'created_by' => $users->random()->id,
            ])
            ->create();
    }
}
