<?php

namespace Tests\Unit\Listeners;

use App\Enums\Task\TaskStatus;
use App\Events\Task\TaskStatusChanged;
use App\Jobs\SendTaskStatusChangedNotification as SendTaskStatusChangedNotificationJob;
use App\Listeners\SendTaskStatusChangedNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTaskStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_handles_task_status_changed_event(): void
    {
        // Arrange
        Queue::fake();

        $changedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending, 'assigned_to' => $assignedTo->id]);

        $event = new TaskStatusChanged($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $listener = new SendTaskStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushed(SendTaskStatusChangedNotificationJob::class, function ($job) use ($task, $changedBy) {
            return $job->task->id === $task->id
                && $job->oldStatus === TaskStatus::Pending
                && $job->newStatus === TaskStatus::InProgress
                && $job->changedBy->id === $changedBy->id;
        });
    }

    #[Test]
    public function listener_does_not_dispatch_job_when_task_has_no_assigned_user(): void
    {
        // Arrange
        Queue::fake();

        $task = Task::factory()->createQuietly(['assigned_to' => null, 'status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();

        $event = new TaskStatusChanged($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $listener = new SendTaskStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertNotPushed(SendTaskStatusChangedNotificationJob::class);
    }

    #[Test]
    public function listener_uses_correct_queue(): void
    {
        // Arrange
        Queue::fake();

        $changedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending, 'assigned_to' => $assignedTo->id]);

        $event = new TaskStatusChanged($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);
        $listener = new SendTaskStatusChangedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushedOn('notifications', SendTaskStatusChangedNotificationJob::class);
    }

    #[Test]
    public function listener_is_queued(): void
    {
        // Arrange
        $listener = new SendTaskStatusChangedNotification;

        // Assert
        $this->assertTrue($listener instanceof \Illuminate\Contracts\Queue\ShouldQueue);
        $this->assertEquals('notifications', $listener->queue);
    }

    #[Test]
    public function listener_handles_all_status_transitions(): void
    {
        // Arrange
        Queue::fake();

        $changedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending, 'assigned_to' => $assignedTo->id]);

        $listener = new SendTaskStatusChangedNotification;

        $transitions = [
            [TaskStatus::Pending, TaskStatus::InProgress],
            [TaskStatus::InProgress, TaskStatus::Completed],
            [TaskStatus::Completed, TaskStatus::Pending],
        ];

        foreach ($transitions as [$oldStatus, $newStatus]) {
            // Act
            $event = new TaskStatusChanged($task, $oldStatus, $newStatus, $changedBy);
            $listener->handle($event);

            // Assert
            Queue::assertPushed(SendTaskStatusChangedNotificationJob::class, function ($job) use ($task, $oldStatus, $newStatus, $changedBy) {
                return $job->task->id === $task->id
                    && $job->oldStatus === $oldStatus
                    && $job->newStatus === $newStatus
                    && $job->changedBy->id === $changedBy->id;
            });
        }
    }
}
