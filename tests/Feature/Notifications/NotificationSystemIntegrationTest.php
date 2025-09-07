<?php

namespace Tests\Feature\Notifications;

use App\Enums\Project\ProjectStatus;
use App\Enums\Task\TaskStatus;
use App\Jobs\SendProjectStatusChangedNotification;
use App\Jobs\SendTaskAssignedNotification;
use App\Jobs\SendTaskStatusChangedNotification;
use App\Mail\ProjectStatusChangedMail;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskStatusChangedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function complete_task_lifecycle_triggers_all_notifications(): void
    {
        // Arrange
        Mail::fake();
        Queue::fakeExcept([
            \App\Listeners\SendProjectStatusChangedNotification::class,
            \App\Listeners\SendTaskAssignedNotification::class,
            \App\Listeners\SendTaskStatusChangedNotification::class,
        ]);

        $otherAdmin = User::factory()->admin()->create();
        $projectCreator = User::factory()->admin()->create();
        $taskAssignee = User::factory()->regularUser()->create();
        $taskCreator = User::factory()->manager()->create();

        $project = Project::factory()->createQuietly([
            'created_by' => $projectCreator->id,
            'status' => ProjectStatus::Active,
        ]);

        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $taskCreator->id,
            'assigned_to' => null,
            'status' => TaskStatus::Pending,
            'title' => 'Test Task',
        ]);

        // Act 1: Assign task via API
        $token = $this->authenticateUser($taskCreator);
        $this->withHeaders($this->getAuthHeader($token))->put("/api/tasks/{$task->id}", [
            'assigned_to' => $taskAssignee->id,
        ])->assertStatus(200);

        // // Act 2: Change task status via API
        $token = $this->authenticateUser($taskAssignee);
        $this->withHeaders($this->getAuthHeader($token))->put("/api/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
        ])->assertStatus(200);

        // // Act 3: Change project status via API
        $token = $this->authenticateUser($otherAdmin);
        $this->withHeaders($this->getAuthHeader($token))->put("/api/projects/{$project->id}", [
            'status' => ProjectStatus::Completed->value,
        ])->assertStatus(200);

        // Assert task assignment notification
        Queue::assertPushed(SendTaskAssignedNotification::class, 1);

        // // Assert task status change notification
        Queue::assertPushed(SendTaskStatusChangedNotification::class, 1);

        // // Assert project status change notifications (creator + assignee)
        Queue::assertPushed(SendProjectStatusChangedNotification::class, 2);

        // // Verify recipients
        Queue::assertPushed(SendProjectStatusChangedNotification::class, function ($job) use ($projectCreator) {
            return $job->recipient->id === $projectCreator->id;
        });

        Queue::assertPushed(SendProjectStatusChangedNotification::class, function ($job) use ($taskAssignee) {
            return $job->recipient->id === $taskAssignee->id;
        });
    }

    #[Test]
    public function notification_jobs_can_be_executed_successfully(): void
    {
        // Arrange
        Mail::fake();

        $projectCreator = User::factory()->admin()->create();
        $assignedBy = User::factory()->admin()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $changedBy = User::factory()->manager()->create();
        $projectChangedBy = User::factory()->admin()->create();
        $recipient = User::factory()->regularUser()->create();

        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($projectCreator);
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id]);
        $task = Task::factory()->createQuietly(['project_id' => $project->id, 'created_by' => $projectCreator->id]);
        $this->forgetMock(AuthServiceInterface::class);

        // Assign task via API first
        $token = $this->authenticateUser($assignedBy);
        $this->withHeaders($this->getAuthHeader($token))->put("/api/tasks/{$task->id}", [
            'assigned_to' => $assignedTo->id,
        ])->assertStatus(200);
        Mail::assertSent(TaskAssignedMail::class, 1);

        // Act & Assert - Execute each job type
        $taskAssignedJob = new SendTaskAssignedNotification($task, $assignedBy);
        $taskAssignedJob->handle();
        Mail::assertSent(TaskAssignedMail::class, 2);

        $taskStatusJob = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $taskStatusJob->handle();
        Mail::assertSent(TaskStatusChangedMail::class, 1);

        $projectStatusJob = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $projectChangedBy,
            $recipient
        );
        $projectStatusJob->handle();
        Mail::assertSent(ProjectStatusChangedMail::class, 1);

        // Verify total emails sent
        Mail::assertSent(TaskAssignedMail::class, 2);
        Mail::assertSent(TaskStatusChangedMail::class, 1);
        Mail::assertSent(ProjectStatusChangedMail::class, 1);
    }

    #[Test]
    public function notification_system_handles_edge_cases_gracefully(): void
    {
        // Arrange
        Queue::fakeExcept([
            \App\Listeners\SendProjectStatusChangedNotification::class,
            \App\Listeners\SendTaskAssignedNotification::class,
            \App\Listeners\SendTaskStatusChangedNotification::class,
        ]);

        $projectCreator = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $project = Project::factory()->createQuietly(['created_by' => $projectCreator->id, 'status' => ProjectStatus::Active]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $projectCreator->id,
            'assigned_to' => null,
            'status' => TaskStatus::Pending,
        ]);

        // Act 1: Try to assign task to null user via API
        $projectCreatorToken = $this->authenticateUser($projectCreator);
        $this
            ->withHeaders($this->getAuthHeader($projectCreatorToken))
            ->put("/api/tasks/{$task->id}", [
                'assigned_to' => null,
            ])
            ->assertStatus(200);

        // Act 2: Try to change task status when no one is assigned via API
        $this
            ->withHeaders($this->getAuthHeader($projectCreatorToken))
            ->put("/api/tasks/{$task->id}", [
                'status' => TaskStatus::InProgress->value,
            ])
            ->assertStatus(200);

        // Act 3: Change project status via API (should work normally)
        $otherAdminToken = $this->authenticateUser($otherAdmin);
        $this
            ->withHeaders($this->getAuthHeader($otherAdminToken))
            ->put("/api/projects/{$project->id}", [
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertStatus(200);

        // Assert - Only project status change should trigger notification
        Queue::assertPushed(SendTaskAssignedNotification::class, 0);
        Queue::assertPushed(SendTaskStatusChangedNotification::class, 0);
        Queue::assertPushed(SendProjectStatusChangedNotification::class, 1);
    }

    #[Test]
    public function notification_emails_contain_proper_content(): void
    {
        // Arrange
        Mail::fake();

        $createdBy = User::factory()->admin()->create([
            'first_name' => 'John',
            'last_name' => 'Creator',
        ]);
        $assignedTo = User::factory()->regularUser()->create([
            'first_name' => 'Jane',
            'last_name' => 'Assignee',
        ]);
        $changedBy = User::factory()->admin()->create([
            'first_name' => 'Bob',
            'last_name' => 'Changer',
        ]);

        $project = Project::factory()->createQuietly([
            'name' => 'Test Project',
            'description' => 'A test project description',
            'created_by' => $createdBy->id,
        ]);

        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($changedBy);

        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'title' => 'Test Task',
            'description' => 'A test task description',
            'status' => TaskStatus::Pending,
            'assigned_to' => $assignedTo->id,
        ]);

        // Update task status via API to trigger events
        $token = $this->authenticateUser($changedBy);
        $this->withHeaders($this->getAuthHeader($token))->put("/api/tasks/{$task->id}", [
            'status' => TaskStatus::InProgress->value,
        ])->assertStatus(200);

        // Act - Execute jobs to send emails
        $taskAssignedJob = new SendTaskAssignedNotification($task, $changedBy);
        $taskAssignedJob->handle();

        $taskStatusJob = new SendTaskStatusChangedNotification($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $taskStatusJob->handle();

        $projectStatusJob = new SendProjectStatusChangedNotification(
            $project,
            ProjectStatus::Active,
            ProjectStatus::Completed,
            $changedBy,
            $assignedTo
        );
        $projectStatusJob->handle();

        // Assert - Verify email subjects
        Mail::assertSent(TaskAssignedMail::class, function ($mail) {
            return str_contains($mail->envelope()->subject, 'Task Assigned');
        });

        Mail::assertSent(TaskStatusChangedMail::class, function ($mail) {
            return str_contains($mail->envelope()->subject, 'Task Status Updated');
        });

        Mail::assertSent(ProjectStatusChangedMail::class, function ($mail) {
            return str_contains($mail->envelope()->subject, 'Project Status Updated');
        });
    }
}
