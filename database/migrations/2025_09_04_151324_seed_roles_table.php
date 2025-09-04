<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'permissions' => [
                    // User management
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    // Role management
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    // Project management
                    'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
                    'projects.manage_all',
                    // Task management
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                    'tasks.assign', 'tasks.manage_all',
                    // System permissions
                    'system.admin',
                ],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'manager',
                'name' => 'Manager',
                'permissions' => [
                    // Project management (own projects)
                    'projects.view', 'projects.create', 'projects.edit_own',
                    // Task management (in own projects)
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                    'tasks.assign', 'tasks.manage_own_projects',
                    // User management (limited)
                    'users.view',
                    // Can view reports
                    'reports.view',
                ],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'user',
                'name' => 'User',
                'permissions' => [
                    // Task management (assigned tasks only)
                    'tasks.view_assigned', 'tasks.update_status',
                    // Project viewing (own projects)
                    'projects.view_own',
                    // Profile management
                    'profile.edit',
                ],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the default roles
        Role::whereIn('slug', ['admin', 'manager', 'user'])->delete();
    }
};
