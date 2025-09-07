<?php

namespace Tests\Unit\Events;

use App\Enums\Task\TaskStatus;
use App\Events\Task\TaskStatusChanged;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TaskStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    private AuthServiceInterface $authService;

    protected function setUp(): void
    {
        parent::setUp();

        /** @phpstan-ignore-next-line */
        $this->authService = $this->mock(AuthServiceInterface::class);
        Event::fake()->except([
            'eloquent.updated: '.Task::class,
        ]);
    }

    #[Test]
    public function task_status_changed_event_can_be_created(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();

        $this->authService->shouldReceive('user')->andReturn($changedBy);

        // Act
        $task->update(['status' => TaskStatus::InProgress]);

        // Assert
        $this->assertEquals(TaskStatus::InProgress, $task->fresh()->status);
    }

    #[Test]
    public function task_status_changed_event_dispatches_correctly(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();

        $this->authService->shouldReceive('user')->andReturn($changedBy);

        // Act
        $task->update(['status' => TaskStatus::InProgress]);

        // Assert
        Event::assertDispatched(TaskStatusChanged::class, function ($event) use ($task, $changedBy) {
            return $event->task->id === $task->id
                && $event->oldStatus === TaskStatus::Pending
                && $event->newStatus === TaskStatus::InProgress
                && $event->changedBy->id === $changedBy->id;
        });
    }

    #[Test]
    public function task_status_changed_event_not_dispatched_when_status_unchanged(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();

        $this->authService->shouldReceive('user')->andReturn($changedBy);

        // Act
        $task->update(['status' => $task->status]);

        // Assert
        Event::assertNotDispatched(TaskStatusChanged::class);
    }

    #[Test]
    public function task_status_changed_event_contains_correct_data(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();
        $this->authService->shouldReceive('user')->andReturn($changedBy);

        // Act
        $event = new TaskStatusChanged($task, TaskStatus::Pending, TaskStatus::InProgress, $changedBy);

        // Assert
        $this->assertEquals($task->id, $event->task->id);
        $this->assertEquals(TaskStatus::Pending, $event->oldStatus);
        $this->assertEquals(TaskStatus::InProgress, $event->newStatus);
        $this->assertEquals($changedBy->id, $event->changedBy->id);
    }

    #[Test]
    #[TestWith([TaskStatus::InProgress])]
    #[TestWith([TaskStatus::Completed])]
    public function task_status_changed_handles_status_transition(TaskStatus $newStatus): void
    {
        $task = Task::factory()->createQuietly(['status' => TaskStatus::Pending]);
        $changedBy = User::factory()->regularUser()->create();
        $this->authService->shouldReceive('user')->andReturn($changedBy);

        $task->update(['status' => $newStatus]);

        $this->assertEquals($newStatus, $task->fresh()->status);
    }
}
