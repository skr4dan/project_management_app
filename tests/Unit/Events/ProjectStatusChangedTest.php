<?php

namespace Tests\Unit\Events;

use App\Enums\Project\ProjectStatus;
use App\Events\Project\ProjectStatusChanged;
use App\Models\Project;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProjectStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake()->except([
            'eloquent.updated: '.Project::class,
            'eloquent.created: '.Project::class,
        ]);
    }

    #[Test]
    public function project_status_changed_event_can_be_created(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();
        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($changedBy);

        // Act
        $project->update([
            'status' => ProjectStatus::Completed,
        ]);

        // Assert
        $this->assertEquals(ProjectStatus::Completed, $project->fresh()->status);
    }

    #[Test]
    public function project_status_changed_event_dispatches_correctly(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();
        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($changedBy);

        // Act
        $project->update([
            'status' => ProjectStatus::Completed,
        ]);

        // Assert
        Event::assertDispatched(ProjectStatusChanged::class, function ($event) use ($project, $changedBy) {
            return $event->project->id === $project->id
                && $event->oldStatus === ProjectStatus::Active
                && $event->newStatus === ProjectStatus::Completed
                && $event->changedBy->id === $changedBy->id;
        });
    }

    #[Test]
    public function project_status_changed_event_not_dispatched_when_status_unchanged(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();

        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($changedBy);

        // Act
        $project->update([
            'status' => ProjectStatus::Active,
        ]);

        // Assert
        Event::assertNotDispatched(ProjectStatusChanged::class);
    }

    #[Test]
    public function project_status_changed_event_contains_correct_data(): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();

        // Act
        $event = new ProjectStatusChanged($project, ProjectStatus::Active, ProjectStatus::Completed, $changedBy);

        // Assert
        $this->assertEquals($project->id, $event->project->id);
        $this->assertEquals(ProjectStatus::Active, $event->oldStatus);
        $this->assertEquals(ProjectStatus::Completed, $event->newStatus);
        $this->assertEquals($changedBy->id, $event->changedBy->id);
    }

    #[Test]
    #[TestWith([ProjectStatus::Completed])]
    #[TestWith([ProjectStatus::Archived])]
    public function project_status_changed_handles_all_status_transitions(ProjectStatus $newStatus): void
    {
        // Arrange
        $project = Project::factory()->createQuietly(['status' => ProjectStatus::Active]);
        $changedBy = User::factory()->regularUser()->create();

        /** @phpstan-ignore-next-line */
        $this->mock(AuthServiceInterface::class)->shouldReceive('user')->andReturn($changedBy);

        // Act
        $project->update([
            'status' => $newStatus,
        ]);

        // Assert
        $this->assertEquals($newStatus, $project->fresh()->status);
    }
}
