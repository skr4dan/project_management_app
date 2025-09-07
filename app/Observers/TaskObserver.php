<?php

namespace App\Observers;

use App\Enums\Task\TaskStatus;
use App\Events\Task\TaskAssigned;
use App\Events\Task\TaskStatusChanged;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
        //
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        Log::info("Task created: {$task->id} - {$task->title}", [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'created_by' => $task->created_by,
        ]);

        if ($task->assigned_to) {
            $this->handleAssignmentChange($task);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Track assignment changes
        if ($task->wasChanged('assigned_to')) {
            $this->handleAssignmentChange($task);
        }

        // Track status changes
        if ($task->wasChanged('status')) {
            $this->handleStatusChange($task);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Log::info("Task deleted: {$task->id} - {$task->title}", [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        Log::info("Task restored: {$task->id} - {$task->title}", [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        Log::warning("Task force deleted: {$task->id} - {$task->title}", [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    /**
     * Handle assignment changes for the task.
     */
    protected function handleAssignmentChange(Task $task): void
    {
        $oldAssignedTo = $task->getOriginal('assigned_to');
        $newAssignedTo = $task->assigned_to;

        // Skip if assignment hasn't actually changed
        if ($oldAssignedTo === $newAssignedTo) {
            return;
        }

        // Get the user who made the change (from the current authenticated user or system)
        $changedBy = $this->authService->user();

        if (! $changedBy) {
            Log::warning("Could not determine who changed task assignment for task {$task->id}");

            return;
        }

        // Get the previously assigned user if any
        $previouslyAssigned = $oldAssignedTo ? User::find($oldAssignedTo) : null;

        // Ensure it's a User or null, not a Collection
        if ($previouslyAssigned instanceof \Illuminate\Database\Eloquent\Collection) {
            $previouslyAssigned = $previouslyAssigned->first();
        }

        // Fire the event
        TaskAssigned::dispatch($task, $changedBy, $previouslyAssigned);

        Log::info("Task assignment changed: {$task->id}", [
            'task_id' => $task->id,
            'old_assigned_to' => $oldAssignedTo,
            'new_assigned_to' => $newAssignedTo,
            'changed_by' => $changedBy->id,
        ]);
    }

    /**
     * Handle status changes for the task.
     */
    protected function handleStatusChange(Task $task): void
    {
        $oldStatus = TaskStatus::from($task->getOriginal('status')->value);
        $newStatus = $task->status;

        // Skip if status hasn't actually changed
        if ($oldStatus === $newStatus) {
            return;
        }

        // Get the user who made the change
        $changedBy = $this->authService->user();

        if (! $changedBy) {
            Log::warning("Could not determine who changed task status for task {$task->id}");

            return;
        }

        // Fire the event
        TaskStatusChanged::dispatch($task, $oldStatus, $newStatus, $changedBy);

        Log::info("Task status changed: {$task->id}", [
            'task_id' => $task->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy->id,
        ]);
    }
}
