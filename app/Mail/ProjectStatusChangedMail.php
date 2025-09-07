<?php

namespace App\Mail;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification for project status changes.
 */
class ProjectStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Project $project,
        public readonly ProjectStatus $oldStatus,
        public readonly ProjectStatus $newStatus,
        public readonly User $changedBy,
        public readonly User $recipient
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
            subject: 'Project Status Updated: '.$this->project->name,
            from: config('notifications.email.from_address'),
            replyTo: [$this->changedBy->email],
            tags: [
                'notification',
                'project-status-changed',
                'project:'.$this->project->id,
                'user:'.$this->recipient->id,
                'old-status:'.$this->oldStatus->value,
                'new-status:'.$this->newStatus->value,
            ],
            metadata: [
                'project_id' => $this->project->id,
                'old_status' => $this->oldStatus->value,
                'new_status' => $this->newStatus->value,
                'changed_by' => $this->changedBy->id,
                'recipient_id' => $this->recipient->id,
                'notification_type' => 'project_status_changed',
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
                'X-Notification-Type' => 'project-status-changed',
                'X-Project-ID' => (string) $this->project->id,
                'X-Changed-By' => (string) $this->changedBy->id,
                'X-Recipient' => (string) $this->recipient->id,
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
            view: 'emails.project-status-changed',
            text: 'emails.project-status-changed-text',
            with: [
                'project' => $this->project,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'changedBy' => $this->changedBy,
                'recipient' => $this->recipient,
                'createdBy' => $this->project->createdBy,
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
        return url("/projects/{$this->project->id}");
    }

    /**
     * Generate the unsubscribe URL for the email.
     */
    protected function generateUnsubscribeUrl(): string
    {
        return url("/notifications/unsubscribe?user={$this->recipient->id}&type=project_status");
    }

    /**
     * Calculate the priority of the email based on status change.
     */
    protected function calculatePriority(): int
    {
        // High priority for major project changes
        if ($this->newStatus === ProjectStatus::Completed ||
            $this->newStatus === ProjectStatus::Archived) {
            return 1; // High priority
        }

        // Medium priority for starting a project
        if ($this->oldStatus === ProjectStatus::Active &&
            in_array($this->newStatus, [ProjectStatus::Completed, ProjectStatus::Archived])) {
            return 3; // Normal priority
        }

        return 5; // Low priority
    }
}
