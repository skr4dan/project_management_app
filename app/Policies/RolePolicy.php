<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     * Only users with roles.view permission can view roles.
     */
    public function viewAny(User $user): Response
    {
        return $user->role?->hasPermission('roles.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view roles.');
    }

    /**
     * Determine whether the user can view a specific role.
     * Only users with roles.view permission can view roles.
     */
    public function view(User $user, Role $role): Response
    {
        return $user->role?->hasPermission('roles.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view roles.');
    }

    /**
     * Determine whether the user can create roles.
     * Only users with roles.create permission can create roles.
     */
    public function create(User $user): Response
    {
        return $user->role?->hasPermission('roles.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create roles.');
    }

    /**
     * Determine whether the user can update a role.
     * Only users with roles.edit permission can update roles.
     */
    public function update(User $user, Role $role): Response
    {
        return $user->role?->hasPermission('roles.edit')
            ? Response::allow()
            : Response::deny('You do not have permission to update roles.');
    }

    /**
     * Determine whether the user can delete a role.
     * Only users with roles.delete permission can delete roles.
     */
    public function delete(User $user, Role $role): Response
    {
        return $user->role?->hasPermission('roles.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete roles.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): Response
    {
        return Response::deny('Role restoration is not allowed.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): Response
    {
        return Response::deny('Permanent deletion is not allowed.');
    }
}
