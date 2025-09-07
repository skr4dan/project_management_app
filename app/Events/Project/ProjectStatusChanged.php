<?php

namespace App\Events\Project;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

/**
 * Event fired when a project status is changed.
 *
 * @property-read Project $project The project whose status changed
 * @property-read ProjectStatus $oldStatus The previous status
 * @property-read ProjectStatus $newStatus The new status
 * @property-read User $changedBy The user who changed the status
 */
class ProjectStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The event name for broadcasting and logging.
     */
    public const NAME = 'project.status.changed';

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Project $project,
        public readonly ProjectStatus $oldStatus,
        public readonly ProjectStatus $newStatus,
        public readonly User $changedBy
    ) {
        Context::add([
            'event' => self::NAME,
            'project_id' => $project->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy->id,
        ]);

        // Log the event for debugging and monitoring
        logger()->info('Project status changed event fired', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy->email,
            'created_by' => $project->createdBy->email,
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
            // new PrivateChannel("projects.{$this->project->id}"),
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
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by' => [
                'id' => $this->changedBy->id,
                'name' => $this->changedBy->first_name.' '.$this->changedBy->last_name,
            ],
            'created_by' => [
                'id' => $this->project->createdBy->id,
                'name' => $this->project->createdBy->first_name.' '.$this->project->createdBy->last_name,
            ],
            'changed_at' => now()->toISOString(),
            'task_count' => $this->project->tasks()->count(),
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
        return config('notifications.project_status.queue', 'notifications');
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'project:'.$this->project->id,
            'user:'.$this->changedBy->id,
            'project-status-change',
        ];
    }

    /**
     * Get the event's unique identifier for deduplication.
     */
    public function uniqueId(): string
    {
        return self::NAME.':'.$this->project->id.':'.now()->timestamp;
    }
}
