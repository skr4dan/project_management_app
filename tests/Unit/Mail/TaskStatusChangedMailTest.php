<?php

namespace Tests\Unit\Mail;

use App\Enums\Task\TaskStatus;
use App\Mail\TaskStatusChangedMail;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskStatusChangedMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_status_changed_mail_has_correct_subject(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['title' => 'Test Task']);
        $changedBy = User::factory()->regularUser()->create();

        $mail = new TaskStatusChangedMail($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Task Status Updated: Test Task', $envelope->subject);
    }

    #[Test]
    public function task_status_changed_mail_uses_correct_view(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $mail = new TaskStatusChangedMail($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals('emails.task-status-changed', $content->view);
    }

    #[Test]
    public function task_status_changed_mail_passes_correct_data_to_view(): void
    {
        // Arrange
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => TaskStatus::InProgress,
            'assigned_to' => $assignedTo->id,
        ]);

        $mail = new TaskStatusChangedMail($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals($task->id, $content->with['task']->id);
        $this->assertEquals(TaskStatus::Pending, $content->with['oldStatus']);
        $this->assertEquals(TaskStatus::InProgress, $content->with['newStatus']);
        $this->assertEquals($changedBy->id, $content->with['changedBy']->id);
        $this->assertEquals($assignedTo->id, $content->with['assignedTo']->id);
    }

    #[Test]
    public function task_status_changed_mail_handles_all_status_transitions(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $transitions = [
            [TaskStatus::Pending, TaskStatus::InProgress],
            [TaskStatus::InProgress, TaskStatus::Completed],
            [TaskStatus::Completed, TaskStatus::Pending],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            // Act
            $mail = new TaskStatusChangedMail($task, $oldStatus, $newStatus, $changedBy);
            $content = $mail->content();

            // Assert
            $this->assertEquals($oldStatus, $content->with['oldStatus']);
            $this->assertEquals($newStatus, $content->with['newStatus']);
        }
    }

    #[Test]
    public function task_status_changed_mail_has_no_attachments(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $changedBy = User::factory()->regularUser()->create();

        $mail = new TaskStatusChangedMail($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $attachments = $mail->attachments();

        // Assert
        $this->assertEmpty($attachments);
    }

    #[Test]
    public function task_status_changed_mail_handles_task_without_assigned_user(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['assigned_to' => null]);
        $changedBy = User::factory()->regularUser()->create();

        $mail = new TaskStatusChangedMail($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertNull($content->with['assignedTo']);
    }
}
