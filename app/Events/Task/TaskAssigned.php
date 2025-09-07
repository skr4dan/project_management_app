<?php

namespace App\Events\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

/**
 * Event fired when a task is assigned to a user.
 *
 * @property-read Task $task The task that was assigned
 * @property-read User $assignedBy The user who assigned the task
 * @property-read User|null $previouslyAssigned The previously assigned user, if any
 */
class TaskAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The event name for broadcasting and logging.
     */
    public const NAME = 'task.assigned';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Task $task,
        public readonly User $assignedBy,
        public readonly ?User $previouslyAssigned = null
    ) {
        Context::add([
            'event' => self::NAME,
            'task_id' => $task->id,
            'assigned_by' => $assignedBy->id,
            'assigned_to' => $task->assigned_to,
            'previously_assigned' => $previouslyAssigned?->id,
        ]);

        // Log the event for debugging and monitoring
        logger()->info('Task assigned event fired', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'assigned_by' => $assignedBy->email,
            'assigned_to' => $task->assignedTo?->email,
            'previously_assigned' => $previouslyAssigned?->email,
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
            'assigned_to' => [
                'id' => $this->task->assignedTo?->id,
                'name' => $this->task->assignedTo?->first_name.' '.$this->task->assignedTo?->last_name,
                'email' => $this->task->assignedTo?->email,
            ],
            'assigned_by' => [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->first_name.' '.$this->assignedBy->last_name,
            ],
            'assigned_at' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if the task has an assigned user
        return $this->task->assigned_to !== null;
    }
}
