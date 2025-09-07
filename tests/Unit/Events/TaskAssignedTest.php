<?php

namespace Tests\Unit\Events;

use App\Events\Task\TaskAssigned;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskAssignedTest extends TestCase
{
    use RefreshDatabase;

    private AuthServiceInterface $authService;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        /** @phpstan-ignore-next-line */
        $this->authService = $this->mock(AuthServiceInterface::class);
    }

    #[Test]
    public function task_assigned_event_can_be_created(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();

        $this->authService->shouldReceive('user')->andReturn($assignedBy);

        // Act
        $result = $task->update([
            'assigned_to' => $assignedTo->id,
        ]);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals($assignedTo->id, $task->fresh()->assigned_to);
    }

    #[Test]
    public function task_assigned_event_dispatches_correctly(): void
    {
        Event::fake()->except([
            'eloquent.updated: '.Task::class,
        ]);

        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();

        $this->authService->shouldReceive('user')->andReturn($assignedBy);

        // Act
        $task->update([
            'assigned_to' => $assignedTo->id,
        ]);

        Event::assertDispatched(TaskAssigned::class, function ($event) use ($task, $assignedBy) {
            return $event->task->id === $task->id
                && $event->assignedBy->id === $assignedBy->id;
        });
    }

    #[Test]
    public function task_assigned_event_dispatches_with_previously_assigned_user(): void
    {
        Event::fake()->except([
            'eloquent.updated: '.Task::class,
        ]);

        // Arrange
        $previouslyAssigned = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly(['assigned_to' => $previouslyAssigned->id]);
        $assignedBy = User::factory()->regularUser()->create();
        $newAssignee = User::factory()->regularUser()->create();

        // Act
        $this->authService->shouldReceive('user')->andReturn($assignedBy);
        $task->update([
            'assigned_to' => $newAssignee->id,
        ]);

        // Assert
        Event::assertDispatched(TaskAssigned::class, function ($event) use ($task, $assignedBy, $previouslyAssigned) {
            return $event->task->id === $task->id
                && $event->assignedBy->id === $assignedBy->id
                && $event->previouslyAssigned->id === $previouslyAssigned->id;
        });
    }

    #[Test]
    public function task_assigned_event_contains_correct_data(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();
        $previouslyAssigned = User::factory()->regularUser()->create();

        $task->assigned_to = $previouslyAssigned->id;
        $task->save();

        // Act
        $event = new TaskAssigned($task, $assignedBy, $previouslyAssigned);

        // Assert
        $this->assertEquals($task->id, $event->task->id);
        $this->assertEquals($assignedBy->id, $event->assignedBy->id);
        $this->assertEquals($previouslyAssigned->id, $event->previouslyAssigned->id);
        $this->assertEquals(TaskAssigned::NAME, $event::NAME);
    }

    #[Test]
    public function task_assigned_event_handles_null_previously_assigned(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();

        // Act
        $event = new TaskAssigned($task, $assignedBy, null);

        // Assert
        $this->assertNull($event->previouslyAssigned);
        $this->assertEquals(TaskAssigned::NAME, $event::NAME);
    }

    #[Test]
    public function task_assigned_event_includes_proper_context(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly(['title' => 'Test Task']);
        $assignedBy = User::factory()->create(['email' => 'assigner@example.com']);
        $assignedTo = User::factory()->create(['email' => 'assignee@example.com']);

        // Act
        $event = new TaskAssigned($task, $assignedBy, null);

        // Assert - Check that context is properly set
        $this->assertEquals(TaskAssigned::NAME, $event::NAME);
        $this->assertEquals($task->id, $event->task->id);
        $this->assertEquals($assignedBy->id, $event->assignedBy->id);
    }

    #[Test]
    public function task_assigned_event_broadcasting_configuration(): void
    {
        // Arrange
        $task = Task::factory()->createQuietly();
        $assignedBy = User::factory()->regularUser()->create();
        $assignedTo = User::factory()->regularUser()->create();

        $task->assigned_to = $assignedTo->id;
        $task->save();

        // Act
        $event = new TaskAssigned($task, $assignedBy, null);

        // Assert - Check broadcasting configuration
        $this->assertEquals(TaskAssigned::NAME, $event->broadcastAs());
        $this->assertTrue($event->broadcastWhen()); // Should broadcast when task has assigned user

        $broadcastData = $event->broadcastWith();
        $this->assertArrayHasKey('task_id', $broadcastData);
        $this->assertArrayHasKey('assigned_to', $broadcastData);
        $this->assertArrayHasKey('assigned_by', $broadcastData);
    }
}
