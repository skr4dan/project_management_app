<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     * Any authenticated user can view tasks (filtering happens at repository level).
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can view a specific task.
     * Any authenticated user can view any task.
     */
    public function view(User $user, Task $task): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can create tasks.
     * Only users with tasks.create permission can create tasks.
     */
    public function create(User $user): Response
    {
        return $user->role?->hasPermission('tasks.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create tasks.');
    }

    /**
     * Determine whether the user can update a task.
     * User assigned to task, task creator (if not assigned), or users with tasks.manage_all permission can update.
     */
    public function update(User $user, Task $task): Response
    {
        return $task->assigned_to === $user->id ||
               ($task->created_by === $user->id && $task->assigned_to === null) ||
               $user->role?->hasPermission('tasks.manage_all')
            ? Response::allow()
            : Response::deny('You can only update tasks assigned to you or that you created.');
    }

    /**
     * Determine whether the user can delete a task.
     * Task creator or users with tasks.manage_all permission can delete.
     */
    public function delete(User $user, Task $task): Response
    {
        return $task->created_by === $user->id || $user->role?->hasPermission('tasks.manage_all')
            ? Response::allow()
            : Response::deny('You can only delete tasks you created or have manage permissions for.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): Response
    {
        return Response::deny('Task restoration is not allowed.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): Response
    {
        return Response::deny('Permanent deletion is not allowed.');
    }
}
