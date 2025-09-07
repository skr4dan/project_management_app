<?php

namespace Tests\Unit\Listeners;

use App\Enums\Project\ProjectStatus;
use App\Events\Project\ProjectStatusChanged;
use App\Jobs\SendProjectStatusChangedNotification as SendProjectStatusChangedNotificationJob;
use App\Listeners\SendProjectStatusChangedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendProjectStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_handles_project_status_changed_event(): void
    {
        // Arrange
        Queue::fake();

        $projectCreator = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $projectCreator->id,
            'status' => ProjectStatus::Active,
        ]);
        $changedBy = User::factory()->regularUser()->create();

        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);
        $listener = new SendProjectStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, function ($job) use ($project, $changedBy, $projectCreator) {
            return $job->project->id === $project->id
                && $job->oldStatus === ProjectStatus::Active
                && $job->newStatus === ProjectStatus::Completed
                && $job->changedBy->id === $changedBy->id
                && $job->recipient->id === $projectCreator->id;
        });
    }

    #[Test]
    public function listener_sends_notifications_to_all_project_participants(): void
    {
        // Arrange
        Queue::fake();

        $projectCreator = User::factory()->regularUser()->create();
        $taskAssignee1 = User::factory()->regularUser()->create();
        $taskAssignee2 = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create();

        $project = Project::factory()->createQuietly([
            'created_by' => $projectCreator->id,
            'status' => ProjectStatus::Active,
        ]);

        // Create tasks assigned to different users
        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $taskAssignee1->id,
        ]);
        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $taskAssignee2->id,
        ]);

        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);
        $listener = new SendProjectStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert that notifications are sent to all participants
        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 3); // creator + 2 assignees

        $recipients = collect([$projectCreator, $taskAssignee1, $taskAssignee2]);
        foreach ($recipients as $recipient) {
            Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, function ($job) use ($recipient) {
                return $job->recipient->id === $recipient->id;
            });
        }
    }

    #[Test]
    public function listener_excludes_user_who_changed_status_from_notifications(): void
    {
        // Arrange
        Queue::fake();

        $projectCreator = User::factory()->regularUser()->create();
        $changedBy = User::factory()->regularUser()->create(); // This user will change status and should not receive notification

        $project = Project::factory()->createQuietly([
            'created_by' => $projectCreator->id,
            'status' => ProjectStatus::Active,
        ]);

        // Create a task assigned to the user who will change status
        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $changedBy->id,
        ]);

        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);
        $listener = new SendProjectStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert that only project creator receives notification (changedBy is excluded)
        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 1);

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, function ($job) use ($projectCreator) {
            return $job->recipient->id === $projectCreator->id;
        });

        // Ensure changedBy does not receive notification
        Queue::assertNotPushed(SendProjectStatusChangedNotificationJob::class, function ($job) use ($changedBy) {
            return $job->recipient->id === $changedBy->id;
        });
    }

    #[Test]
    public function listener_removes_duplicate_recipients(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->regularUser()->create(); // This user is both creator and assignee
        $changedBy = User::factory()->regularUser()->create();

        $project = Project::factory()->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active,
        ]);

        // Create a task assigned to the same user who created the project
        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
        ]);

        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);
        $listener = new SendProjectStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert that only one notification is sent (no duplicates)
        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, 1);

        Queue::assertPushed(SendProjectStatusChangedNotificationJob::class, function ($job) use ($user) {
            return $job->recipient->id === $user->id;
        });
    }

    #[Test]
    public function listener_uses_correct_queue(): void
    {
        // Arrange
        Queue::fake();

        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();

        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);
        $listener = new SendProjectStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushedOn('notifications', SendProjectStatusChangedNotificationJob::class);
    }

    #[Test]
    public function listener_is_queued(): void
    {
        // Arrange
        $listener = new SendProjectStatusChangedNotification;

        // Assert
        $this->assertTrue($listener instanceof \Illuminate\Contracts\Queue\ShouldQueue);
        $this->assertEquals('notifications', $listener->queue);
    }
}
