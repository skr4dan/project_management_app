<?php

namespace Tests\Feature\Notifications;

use App\Enums\Task\TaskStatus;
use App\Jobs\SendTaskAssignedNotification as SendTaskAssignedNotificationJob;
use App\Listeners\SendTaskAssignedNotification;
use App\Mail\TaskAssignedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskAssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_assignment_triggers_notification_email(): void
    {
        // Arrange
        Mail::fake();
        Queue::fakeExcept([
            SendTaskAssignedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
            'status' => TaskStatus::Pending,
        ]);
        $assignedBy = User::factory()->admin()->create();
        $assignedTo = User::factory()->regularUser()->create();

        // Act - Assign task via API
        $token = $this->authenticateUser($assignedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to.id', $assignedTo->id);

        $this->assertEquals($assignedTo->id, $task->fresh()->assigned_to);

        // Check that job was dispatched
        Queue::assertPushed(SendTaskAssignedNotificationJob::class, function ($job) use ($task, $assignedBy) {
            return $job->task->id === $task->id
                && $job->assignedBy->id === $assignedBy->id;
        });
    }

    #[Test]
    public function task_assignment_notification_sent_via_queue(): void
    {
        // Arrange
        Queue::fakeExcept([
            SendTaskAssignedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
        ]);
        $assignedTo = User::factory()->regularUser()->create();

        // Act - Assign task via API
        $token = $this->authenticateUser($projectCreator);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        // Assert
        Queue::assertPushedOn('notifications', SendTaskAssignedNotificationJob::class);
    }

    #[Test]
    public function task_assignment_notification_job_sends_correct_email(): void
    {
        // Arrange
        Mail::fake();

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
            'title' => 'Test Task',
            'status' => TaskStatus::Pending,
        ]);
        $assignedTo = User::factory()->regularUser()->create();

        // Assign task via API first
        $token = $this->authenticateUser($projectCreator);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        // Assert
        Mail::assertSent(TaskAssignedMail::class, function ($mail) use ($assignedTo) {
            return $mail->hasTo($assignedTo->email);
        });

        Mail::assertSent(TaskAssignedMail::class, 1);
    }

    #[Test]
    public function task_assignment_notification_includes_correct_data(): void
    {
        // Arrange
        Mail::fake();

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id, 'name' => 'Test Project']);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => TaskStatus::InProgress,
        ]);
        $assignedBy = User::factory()->admin()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $assignedTo = User::factory()->regularUser()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Assign task via API first
        $token = $this->authenticateUser($assignedBy);
        $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ])
            ->assertStatus(200);

        $job = new SendTaskAssignedNotificationJob($task, $assignedBy);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(TaskAssignedMail::class, function ($mail) use ($task, $assignedBy, $assignedTo, $project) {
            return $mail->task->id === $task->id
                && $mail->task->assignedTo->id === $assignedTo->id
                && $mail->task->project->id === $project->id
                && $mail->assignedBy->id === $assignedBy->id;
        });
    }

    #[Test]
    public function task_assignment_to_same_user_does_not_trigger_notification(): void
    {
        // Arrange
        Queue::fake();

        $projectCreator = User::factory()->admin()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => $assignedTo->id,
        ]);

        // Act - Try to assign to same user via API
        $token = $this->authenticateUser($projectCreator);
        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => $assignedTo->id,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.assigned_to.id', $assignedTo->id);

        // Task should still be assigned to the same user
        $this->assertEquals($assignedTo->id, $task->fresh()->assigned_to);

        // No new job should be dispatched since assignment didn't change
        Queue::assertNotPushed(SendTaskAssignedNotificationJob::class);
    }

    #[Test]
    public function task_assignment_without_assigned_user_does_not_trigger_notification(): void
    {
        // Arrange
        Queue::fake();

        $projectCreator = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
        ]);
        $assignedBy = User::factory()->admin()->create();

        // Act - Try to assign to null via API
        $token = $this->authenticateUser($assignedBy);
        $response = $this
            ->withHeaders($this->getAuthHeader($token))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => null,
            ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to', null);

        $this->assertNull($task->fresh()->assigned_to);

        // No job should be dispatched
        Queue::assertNotPushed(SendTaskAssignedNotificationJob::class);
    }
}
