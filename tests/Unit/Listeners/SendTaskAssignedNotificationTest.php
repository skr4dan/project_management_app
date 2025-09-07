<?php

namespace Tests\Unit\Listeners;

use App\Events\Task\TaskAssigned;
use App\Jobs\SendTaskAssignedNotification as SendTaskAssignedNotificationJob;
use App\Listeners\SendTaskAssignedNotification;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTaskAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_handles_task_assigned_event(): void
    {
        // Arrange
        Queue::fake();

        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($assignedBy);

        $task->update([
            'assigned_to' => $assignedTo->id,
        ]);

        $event = new TaskAssigned($task, $assignedBy, null);
        $listener = new SendTaskAssignedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushed(SendTaskAssignedNotificationJob::class, function ($job) use ($task, $assignedBy) {
            return $job->task->id === $task->id
                && $job->assignedBy->id === $assignedBy->id;
        });
    }

    #[Test]
    public function listener_does_not_dispatch_job_when_task_has_no_assigned_user(): void
    {
        // Arrange
        Queue::fake();

        $task = Task::factory()->createQuietly(['assigned_to' => null]);
        $assignedBy = User::factory()->regularUser()->create();

        $event = new TaskAssigned($task, $assignedBy, null);
        $listener = new SendTaskAssignedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertNotPushed(SendTaskAssignedNotificationJob::class);
    }

    #[Test]
    public function listener_uses_correct_queue(): void
    {
        // Arrange
        Queue::fake();

        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();

        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($assignedBy);
        $task->update([
            'assigned_to' => $assignedTo->id,
        ]);

        $event = new TaskAssigned($task, $assignedBy, null);
        $listener = new SendTaskAssignedNotification;

        // Act
        $listener->handle($event);

        // Assert
        Queue::assertPushedOn('notifications', SendTaskAssignedNotificationJob::class);
    }

    #[Test]
    public function listener_is_queued(): void
    {
        // Arrange
        $listener = new SendTaskAssignedNotification;

        // Assert
        $this->assertTrue($listener instanceof \Illuminate\Contracts\Queue\ShouldQueue);
        $this->assertEquals('notifications', $listener->queue);
    }
}
