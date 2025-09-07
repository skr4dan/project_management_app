<?php

namespace App\Events\Task;

use App\Enums\Task\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

/**
 * Event fired when a task status is changed.
 *
 * @property-read Task $task The task whose status changed
 * @property-read TaskStatus $oldStatus The previous status
 * @property-read TaskStatus $newStatus The new status
 * @property-read User|null $changedBy The user who changed the status
 */
class TaskStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The event name for broadcasting and logging.
     */
    public const NAME = 'task.status.changed';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $oldStatus,
        public readonly TaskStatus $newStatus,
        public readonly ?User $changedBy
    ) {
        Context::add([
            'event' => self::NAME,
            'task_id' => $task->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy?->id,
        ]);

        // Log the event for debugging and monitoring
        logger()->info('Task status changed event fired', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy?->email,
            'assigned_to' => $task->assignedTo?->email,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Add broadcasting channels if needed for real-time updates
            // new PrivateChannel("projects.{$this->task->project_id}"),
            // new PrivateChannel("tasks.{$this->task->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by' => [
                'id' => $this->changedBy?->id,
                'name' => $this->changedBy?->first_name.' '.$this->changedBy?->last_name,
            ],
            'assigned_to' => $this->task->assignedTo ? [
                'id' => $this->task->assignedTo->id,
                'name' => $this->task->assignedTo->first_name.' '.$this->task->assignedTo->last_name,
            ] : null,
            'changed_at' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if there's an actual status change
        return $this->oldStatus !== $this->newStatus;
    }

    /**
     * Determine the queue the event should be placed on.
     */
    public function broadcastQueue(): string
    {
        return config('notifications.task_status.queue', 'notifications');
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'task:'.$this->task->id,
            'project:'.$this->task->project_id,
            'user:'.$this->changedBy?->id,
            'status-change',
        ];
    }
}
