<?php

namespace App\Mail;

use App\Enums\Task\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification for task status changes.
 */
class TaskStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $oldStatus,
        public readonly TaskStatus $newStatus,
        public readonly User $changedBy
    ) {
        // Set queue from configuration
        $this->queue = config('notifications.queue.default_queue', 'emails');

        // Add delay if configured
        $delay = config('notifications.email.delay', 0);
        if ($delay > 0) {
            $this->delay = now()->addSeconds($delay);
        }

        // Set email priority based on status change
        $this->priority($this->calculatePriority());
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Task Status Updated: '.$this->task->title,
            from: config('notifications.email.from_address'),
            replyTo: [$this->changedBy->email],
            tags: [
                'notification',
                'task-status-changed',
                'task:'.$this->task->id,
                'project:'.$this->task->project_id,
                'user:'.$this->task->assigned_to,
                'old-status:'.$this->oldStatus->value,
                'new-status:'.$this->newStatus->value,
            ],
            metadata: [
                'task_id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'old_status' => $this->oldStatus->value,
                'new_status' => $this->newStatus->value,
                'changed_by' => $this->changedBy->id,
                'assigned_to' => $this->task->assigned_to,
                'notification_type' => 'task_status_changed',
            ],
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Notification-Type' => 'task-status-changed',
                'X-Task-ID' => (string) $this->task->id,
                'X-Project-ID' => (string) $this->task->project_id,
                'X-Changed-By' => (string) $this->changedBy->id,
                'X-Old-Status' => $this->oldStatus->value,
                'X-New-Status' => $this->newStatus->value,
                'X-Priority' => (string) $this->calculatePriority(),
                'X-Auto-Response-Suppress' => 'OOF',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-status-changed',
            text: 'emails.task-status-changed-text',
            with: [
                'task' => $this->task,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'changedBy' => $this->changedBy,
                'assignedTo' => $this->task->assignedTo,
                'project' => $this->task->project,
                'notificationUrl' => $this->generateNotificationUrl(),
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate the notification URL for the email.
     */
    protected function generateNotificationUrl(): string
    {
        return url("/tasks/{$this->task->id}");
    }

    /**
     * Generate the unsubscribe URL for the email.
     */
    protected function generateUnsubscribeUrl(): string
    {
        return url("/notifications/unsubscribe?user={$this->task->assigned_to}&type=task_status");
    }

    /**
     * Calculate the priority of the email based on status change.
     */
    protected function calculatePriority(): int
    {
        // High priority for completion or assignment changes
        if ($this->newStatus === TaskStatus::Completed ||
            $this->oldStatus === TaskStatus::Pending && $this->newStatus === TaskStatus::InProgress) {
            return 1; // High priority
        }

        return 3; // Normal priority
    }
}
