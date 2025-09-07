<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification for task assignment.
 */
class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Task $task,
        public readonly User $assignedBy
    ) {
        // Set queue from configuration
        $this->queue = config('notifications.queue.default_queue', 'emails');

        // Add delay if configured
        $delay = config('notifications.email.delay', 0);
        if ($delay > 0) {
            $this->delay = now()->addSeconds($delay);
        }

        // Set email priority for task assignments (high priority)
        $this->priority(1);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Task Assigned: '.$this->task->title,
            from: config('notifications.email.from_address'),
            replyTo: [$this->assignedBy->email],
            tags: [
                'notification',
                'task-assigned',
                'task:'.$this->task->id,
                'project:'.$this->task->project_id,
                'user:'.$this->task->assigned_to,
            ],
            metadata: [
                'task_id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'assigned_by' => $this->assignedBy->id,
                'assigned_to' => $this->task->assigned_to,
                'notification_type' => 'task_assigned',
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
                'X-Notification-Type' => 'task-assigned',
                'X-Task-ID' => (string) $this->task->id,
                'X-Project-ID' => (string) $this->task->project_id,
                'X-Assigned-By' => (string) $this->assignedBy->id,
                'X-Priority' => 'normal', // Can be 'low', 'normal', 'high'
                'X-Auto-Response-Suppress' => 'OOF', // Suppress out-of-office replies
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-assigned',
            text: 'emails.task-assigned-text', // Plain text version
            with: [
                'task' => $this->task,
                'assignedBy' => $this->assignedBy,
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
        // Generate a signed URL for the task
        return url("/tasks/{$this->task->id}");
    }

    /**
     * Generate the unsubscribe URL for the email.
     */
    protected function generateUnsubscribeUrl(): string
    {
        // Generate a signed URL for unsubscribing from notifications
        return url("/notifications/unsubscribe?user={$this->task->assigned_to}&type=task_assigned");
    }
}
