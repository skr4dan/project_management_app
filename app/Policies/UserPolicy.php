<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Only users with users.view permission can view the list of all users.
     */
    public function viewAny(User $user): Response
    {
        return $user->role?->hasPermission('users.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view users.');
    }

    /**
     * Determine whether the user can view a specific user.
     * Any authenticated user can view any user profile.
     */
    public function view(User $user, User $model): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can create users.
     * Currently not implemented in the application.
     */
    public function create(User $user): Response
    {
        return Response::deny('User creation is not allowed.');
    }

    /**
     * Determine whether the user can update a user.
     * Users with users.edit permission can update any user, or users can update their own profile.
     */
    public function update(User $user, User $model): Response
    {
        return $user->id === $model->id || $user->role?->hasPermission('users.edit')
            ? Response::allow()
            : Response::deny('You can only update your own profile.');
    }

    /**
     * Determine whether the user can delete a user.
     * Currently not implemented in the application.
     */
    public function delete(User $user, User $model): Response
    {
        return Response::deny('User deletion is not allowed.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): Response
    {
        return Response::deny('User restoration is not allowed.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): Response
    {
        return Response::deny('Permanent deletion is not allowed.');
    }
}
