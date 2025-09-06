<?php

namespace Tests\Unit\Repositories;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = new TaskRepository(new \App\Models\Task);
    }

    #[Test]
    public function it_can_find_task_by_id()
    {
        $task = Task::factory()->create();

        $foundTask = $this->taskRepository->findById($task->id);

        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertEquals($task->id, $foundTask->id);
    }

    #[Test]
    public function it_returns_null_when_task_not_found_by_id()
    {
        $foundTask = $this->taskRepository->findById(999);

        $this->assertNull($foundTask);
    }

    #[Test]
    public function it_can_find_tasks_by_project()
    {
        $project = Project::factory()->create();
        Task::factory()->count(3)->create(['project_id' => $project->id]);
        Task::factory()->count(2)->create(); // Different project

        $tasks = $this->taskRepository->getByProject($project->id);

        $this->assertCount(3, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
        $tasks->each(function ($task) use ($project) {
            $this->assertEquals($project->id, $task->project_id);
        });
    }

    #[Test]
    public function it_can_find_tasks_by_assignee()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['assigned_to' => $user->id]);
        Task::factory()->count(1)->create(); // Different assignee

        $tasks = $this->taskRepository->getByAssignee($user->id);

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
        $tasks->each(function ($task) use ($user) {
            $this->assertEquals($user->id, $task->assigned_to);
        });
    }

    #[Test]
    public function it_can_find_tasks_by_creator()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['created_by' => $user->id]);
        Task::factory()->count(1)->create(); // Different creator

        $tasks = $this->taskRepository->getByCreator($user->id);

        $this->assertCount(3, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
        $tasks->each(function ($task) use ($user) {
            $this->assertEquals($user->id, $task->created_by);
        });
    }

    #[Test]
    public function it_can_find_tasks_by_status()
    {
        Task::factory()->count(2)->create(['status' => TaskStatus::Completed->value]);
        Task::factory()->count(3)->create(['status' => TaskStatus::Pending->value]);

        $tasks = $this->taskRepository->getByStatus(TaskStatus::Completed);

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
        $tasks->each(function ($task) {
            $this->assertEquals(TaskStatus::Completed, $task->status);
        });
    }

    #[Test]
    public function it_can_find_tasks_by_priority()
    {
        Task::factory()->count(2)->create(['priority' => TaskPriority::High->value]);
        Task::factory()->count(1)->create(['priority' => TaskPriority::Medium->value]);

        $tasks = $this->taskRepository->getByPriority(TaskPriority::High);

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
        $tasks->each(function ($task) {
            $this->assertEquals(TaskPriority::High, $task->priority);
        });
    }

    #[Test]
    public function it_can_get_overdue_tasks()
    {
        Task::factory()->count(2)->create([
            'due_date' => now()->subDays(1),
            'status' => TaskStatus::Pending->value,
        ]);
        Task::factory()->count(1)->create([
            'due_date' => now()->addDays(1),
            'status' => TaskStatus::Pending->value,
        ]);
        Task::factory()->count(1)->create([
            'due_date' => now()->subDays(1),
            'status' => TaskStatus::Completed->value, // Should not be included
        ]);

        $tasks = $this->taskRepository->getOverdueTasks();

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
    }

    #[Test]
    public function it_can_get_tasks_due_soon()
    {
        Task::factory()->count(2)->create([
            'due_date' => now()->addDays(3),
            'status' => TaskStatus::Pending->value,
        ]);
        Task::factory()->count(1)->create([
            'due_date' => now()->addDays(10),
            'status' => TaskStatus::Pending->value,
        ]);

        $tasks = $this->taskRepository->getTasksDueSoon(5);

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tasks);
    }

    #[Test]
    public function it_can_create_task_from_dto()
    {
        $project = Project::factory()->create();
        $assignee = User::factory()->create();
        $creator = User::factory()->create();

        $taskDTO = new \App\DTOs\Task\TaskDTO(
            id: null,
            title: 'Test Task',
            description: 'Test Description',
            status: TaskStatus::Pending,
            priority: TaskPriority::Medium,
            project_id: $project->id,
            assigned_to: $assignee->id,
            created_by: $creator->id,
            due_date: now()->addDays(7),
            created_at: null,
            updated_at: null
        );

        $task = $this->taskRepository->createFromDTO($taskDTO);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test Description', $task->description);
        $this->assertEquals(TaskStatus::Pending, $task->status);
        $this->assertEquals(TaskPriority::Medium, $task->priority);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals($assignee->id, $task->assigned_to);
        $this->assertEquals($creator->id, $task->created_by);
    }

    #[Test]
    public function it_can_update_task_from_dto()
    {
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'description' => 'Original Description',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Low->value,
        ]);

        $taskDTO = new \App\DTOs\Task\TaskDTO(
            id: $task->id,
            title: 'Updated Title',
            description: 'Updated Description',
            status: TaskStatus::InProgress,
            priority: TaskPriority::High,
            project_id: $task->project_id,
            assigned_to: $task->assigned_to,
            created_by: $task->created_by,
            due_date: $task->due_date,
            created_at: $task->created_at,
            updated_at: $task->updated_at
        );

        $updated = $this->taskRepository->updateFromDTO($task->id, $taskDTO);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Title', $task->fresh()->title);
        $this->assertEquals('Updated Description', $task->fresh()->description);
        $this->assertEquals(TaskStatus::InProgress, $task->fresh()->status);
        $this->assertEquals(TaskPriority::High, $task->fresh()->priority);
    }

    #[Test]
    public function it_returns_false_when_update_from_dto_fails()
    {
        $taskDTO = new \App\DTOs\Task\TaskDTO(
            id: null,
            title: 'Test Task',
            description: 'Test Description',
            status: TaskStatus::Pending,
            priority: TaskPriority::Medium,
            project_id: 1,
            assigned_to: 1,
            created_by: 1,
            due_date: now()->addDays(1),
            created_at: null,
            updated_at: null
        );

        $updated = $this->taskRepository->updateFromDTO(999, $taskDTO);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_update_task_status()
    {
        $task = Task::factory()->create(['status' => TaskStatus::Pending->value]);

        $updated = $this->taskRepository->updateStatus($task->id, TaskStatus::Completed);

        $this->assertTrue($updated);
        $this->assertEquals(TaskStatus::Completed, $task->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_update_status_fails()
    {
        $updated = $this->taskRepository->updateStatus(999, TaskStatus::Completed);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_update_task_priority()
    {
        $task = Task::factory()->create(['priority' => TaskPriority::Low->value]);

        $updated = $this->taskRepository->updatePriority($task->id, TaskPriority::High);

        $this->assertTrue($updated);
        $this->assertEquals(TaskPriority::High, $task->fresh()->priority);
    }

    #[Test]
    public function it_returns_false_when_update_priority_fails()
    {
        $updated = $this->taskRepository->updatePriority(999, TaskPriority::High);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_assign_task_to_user()
    {
        $task = Task::factory()->create(['assigned_to' => null]);
        $user = User::factory()->create();

        $assigned = $this->taskRepository->assignToUser($task->id, $user->id);

        $this->assertTrue($assigned);
        $this->assertEquals($user->id, $task->fresh()->assigned_to);
    }

    #[Test]
    public function it_returns_false_when_assign_to_user_fails()
    {
        $assigned = $this->taskRepository->assignToUser(999, 1);

        $this->assertFalse($assigned);
    }

    #[Test]
    public function it_can_unassign_task_from_user()
    {
        $task = Task::factory()->create();

        $unassigned = $this->taskRepository->unassignFromUser($task->id);

        $this->assertTrue($unassigned);
        $this->assertNull($task->fresh()->assigned_to);
    }

    #[Test]
    public function it_returns_false_when_unassign_from_user_fails()
    {
        $unassigned = $this->taskRepository->unassignFromUser(999);

        $this->assertFalse($unassigned);
    }

    #[Test]
    public function it_can_get_project_statistics()
    {
        $project = Project::factory()->create();
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Completed->value,
        ]);
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending->value,
        ]);

        $stats = $this->taskRepository->getProjectStatistics($project->id);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['completed']);
        $this->assertEquals(3, $stats['pending']);
    }

    #[Test]
    public function it_can_get_user_statistics()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create([
            'assigned_to' => $user->id,
            'status' => TaskStatus::Completed->value,
        ]);
        Task::factory()->count(1)->create([
            'assigned_to' => $user->id,
            'status' => TaskStatus::InProgress->value,
        ]);

        $stats = $this->taskRepository->getUserStatistics($user->id);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['completed']);
        $this->assertEquals(1, $stats['in_progress']);
    }

    #[Test]
    public function it_can_filter_tasks_by_status()
    {
        Task::factory()->count(2)->create(['status' => TaskStatus::Completed->value]);
        Task::factory()->count(3)->create(['status' => TaskStatus::Pending->value]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter(['status' => TaskStatus::Completed->value]);
        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $tasks->each(function ($task) {
            $this->assertEquals(TaskStatus::Completed, $task->status);
        });
    }

    #[Test]
    public function it_can_filter_tasks_by_priority()
    {
        Task::factory()->count(2)->create(['priority' => TaskPriority::High->value]);
        Task::factory()->count(1)->create(['priority' => TaskPriority::Medium->value]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter(['priority' => TaskPriority::High->value]);
        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $tasks->each(function ($task) {
            $this->assertEquals(TaskPriority::High, $task->priority);
        });
    }

    #[Test]
    public function it_can_filter_tasks_by_project()
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        Task::factory()->count(3)->create(['project_id' => $project1->id]);
        Task::factory()->count(2)->create(['project_id' => $project2->id]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter(['project_id' => $project1->id]);
        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(3, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $tasks->each(function ($task) use ($project1) {
            $this->assertEquals($project1->id, $task->project_id);
        });
    }

    #[Test]
    public function it_can_filter_tasks_by_assignee()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Task::factory()->count(2)->create(['assigned_to' => $user1->id]);
        Task::factory()->count(1)->create(['assigned_to' => $user2->id]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter(['assigned_to' => $user1->id]);
        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $tasks->each(function ($task) use ($user1) {
            $this->assertEquals($user1->id, $task->assigned_to);
        });
    }

    #[Test]
    public function it_can_filter_tasks_with_multiple_criteria()
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // Create tasks that match all criteria
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::High->value,
        ]);

        // Create tasks that don't match all criteria
        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => TaskStatus::Completed->value, // Different status
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => User::factory()->create()->id, // Different assignee
            'status' => TaskStatus::Pending->value,
        ]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::High->value,
        ]);

        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $tasks->each(function ($task) use ($project, $user) {
            $this->assertEquals($project->id, $task->project_id);
            $this->assertEquals($user->id, $task->assigned_to);
            $this->assertEquals(TaskStatus::Pending, $task->status);
            $this->assertEquals(TaskPriority::High, $task->priority);
        });
    }

    #[Test]
    #[TestWith(['due_date', 'asc'])]
    #[TestWith(['due_date', 'desc'])]
    #[TestWith(['created_at', 'asc'])]
    #[TestWith(['created_at', 'desc'])]
    public function it_can_filter_tasks_with_sorting(string $sortField, string $sortOrder)
    {
        $project = Project::factory()->create();

        // Create tasks with different timestamps and due dates
        $firstTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'First Task',
            'due_date' => now()->addDays(1),
            'created_at' => now()->subDays(2),
        ]);

        $secondTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Second Task',
            'due_date' => now()->addDays(2),
            'created_at' => now()->subDay(),
        ]);

        $thirdTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Third Task',
            'due_date' => now()->addDays(3),
            'created_at' => now(),
        ]);

        $filter = new \App\Repositories\Criteria\Task\TaskFilter([
            'project_id' => $project->id,
            'sort_by' => $sortField,
            'sort_order' => $sortOrder,
        ]);

        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(3, $tasks);

        if ($sortOrder === 'asc') {
            $this->assertEquals($firstTask->id, $tasks->first()->id);
            $this->assertEquals($thirdTask->id, $tasks->last()->id);
        } else {
            $this->assertEquals($thirdTask->id, $tasks->first()->id);
            $this->assertEquals($firstTask->id, $tasks->last()->id);
        }
    }

    #[Test]
    public function it_can_filter_tasks_with_empty_criteria()
    {
        Task::factory()->count(5)->create();

        $filter = new \App\Repositories\Criteria\Task\TaskFilter([]);
        $paginator = $this->taskRepository->filter($filter);
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $tasks = $paginator->getCollection();

        $this->assertCount(5, $tasks);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
    }
}
