<?php

namespace Tests\Unit\Mail;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Mail\TaskAssignedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskAssignedMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_assigned_mail_has_correct_subject(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['title' => 'Test Task']);
        $assignedBy = User::factory()->regularUser()->create();

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Task Assigned: Test Task', $envelope->subject);
    }

    #[Test]
    public function task_assigned_mail_uses_correct_view(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals('emails.task-assigned', $content->view);
    }

    #[Test]
    public function task_assigned_mail_passes_correct_data_to_view(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();

        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $assignedTo->id,
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertEquals($task->id, $content->with['task']->id);
        $this->assertEquals($assignedBy->id, $content->with['assignedBy']->id);
        $this->assertEquals($assignedTo->id, $content->with['assignedTo']->id);
        $this->assertEquals($project->id, $content->with['project']->id);
    }

    #[Test]
    public function task_assigned_mail_handles_task_without_description(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['description' => null]);
        $assignedBy = User::factory()->regularUser()->create();

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertNull($content->with['task']->description);
    }

    #[Test]
    public function task_assigned_mail_handles_task_without_due_date(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['due_date' => null]);
        $assignedBy = User::factory()->regularUser()->create();

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $content = $mail->content();

        // Assert
        $this->assertNull($content->with['task']->due_date);
    }

    #[Test]
    public function task_assigned_mail_has_no_attachments(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();

        $mail = new TaskAssignedMail($task, $assignedBy);

        // Act
        $attachments = $mail->attachments();

        // Assert
        $this->assertEmpty($attachments);
    }
}
