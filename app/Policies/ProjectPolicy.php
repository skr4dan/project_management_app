<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     * Any authenticated user can view projects.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can view a specific project.
     * Any authenticated user can view any project.
     */
    public function view(User $user, Project $project): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can create projects.
     * Only users with projects.create permission can create projects.
     */
    public function create(User $user): Response
    {
        return $user->role?->hasPermission('projects.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create projects.');
    }

    /**
     * Determine whether the user can update a project.
     * User can update their own projects, or users with projects.manage_all permission can update any project.
     */
    public function update(User $user, Project $project): Response
    {
        return $project->created_by === $user->id || $user->role?->hasPermission('projects.manage_all')
            ? Response::allow()
            : Response::deny('You can only update projects you created or have manage permissions for.');
    }

    /**
     * Determine whether the user can delete a project.
     * User can delete their own projects, or users with projects.manage_all permission can delete any project.
     */
    public function delete(User $user, Project $project): Response
    {
        return $project->created_by === $user->id || $user->role?->hasPermission('projects.manage_all')
            ? Response::allow()
            : Response::deny('You can only delete projects you created or have manage permissions for.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): Response
    {
        return Response::deny('Project restoration is not allowed.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): Response
    {
        return Response::deny('Permanent deletion is not allowed.');
    }
}
