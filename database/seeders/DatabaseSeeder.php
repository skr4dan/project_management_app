<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRoleId = \App\Models\Role::where('slug', 'admin')->firstOrFail()->id;
        $managerRoleId = \App\Models\Role::where('slug', 'manager')->firstOrFail()->id;
        $userRoleId = \App\Models\Role::where('slug', 'user')->firstOrFail()->id;

        // Roles are now created by migration, create test users with different roles
        $userFactory = User::factory()->state(['status' => \App\Enums\User\UserStatus::Active->value]);
        $userFactory->state([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make((string) config('app.demo.admin_password') ?: fake()->password()),
            'role_id' => $adminRoleId,
        ])->create();

        $managerFactory = $userFactory->state(fn () => [
            'first_name' => 'Manager',
            'last_name' => 'Management',
            'role_id' => $managerRoleId,
        ]);
        $managerFactory->state(fn () => [
            'email' => 'manager_'.uniqid().'@example.com',
        ])->createMany(5);
        $managerFactory->create([
            'email' => 'manager@example.com',
            'password' => Hash::make((string) config('app.demo.manager_password') ?: fake()->password()),
        ]);

        $regularUserFactory = $userFactory->state(fn () => [
            'first_name' => 'Regular',
            'last_name' => 'User',
            'role_id' => $userRoleId,
        ]);
        $regularUserFactory->state(fn () => [
            'email' => 'user_'.uniqid().'@example.com',
        ])->createMany(5);
        $regularUserFactory->create([
            'email' => 'user@example.com',
            'password' => Hash::make((string) config('app.demo.user_password') ?: fake()->password()),
        ]);

        $users = User::all();

        $projects = Project::factory()
            ->state(fn ($attributes) => [
                'created_by' => $users->random()->id,
            ])
            ->count(7)
            ->create();

        Task::factory()
            ->count(50)
            ->state(fn () => [
                'project_id' => $projects->random()->id,
                'assigned_to' => $users->random()->id,
                'created_by' => $users->random()->id,
            ])
            ->create();
    }
}
