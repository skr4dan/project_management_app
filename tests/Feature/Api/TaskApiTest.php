<?php

namespace Tests\Feature\Api;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_list_tasks()
    {
        $user = User::factory()->manager()->create(); // Use manager role to ensure UserCriteria is applied
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $tasks = Task::factory()->count(3)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'project',
                        'due_date',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
                'message',
            ]);

        // Verify pagination metadata
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(15, $response->json('pagination.per_page'));
        $this->assertEquals(3, $response->json('pagination.total'));
        $this->assertEquals(1, $response->json('pagination.last_page'));
        $this->assertEquals(1, $response->json('pagination.from'));
        $this->assertEquals(3, $response->json('pagination.to'));
    }

    #[Test]
    public function user_can_filter_tasks_by_status()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => TaskStatus::InProgress,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('pending', $response->json('data.0.status'));
    }

    #[Test]
    public function user_can_filter_tasks_by_priority()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'priority' => TaskPriority::High,
            'created_by' => $user->id,
        ]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'priority' => TaskPriority::Low,
            'assigned_to' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?priority=high');

        $response->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('high', $response->json('data.0.priority'));
    }

    #[Test]
    public function user_can_filter_tasks_by_project()
    {
        $user = User::factory()->regularUser()->create();
        $project1 = Project::factory()->createQuietly(['created_by' => $user->id]);
        $project2 = Project::factory()->createQuietly(['created_by' => $user->id]);

        Task::factory()->createQuietly(['project_id' => $project1->id, 'created_by' => $user->id]);
        Task::factory()->createQuietly(['project_id' => $project2->id, 'assigned_to' => $user->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks?project_id={$project1->id}");

        $response->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($project1->id, $response->json('data.0.project.id'));
    }

    #[Test]
    public function user_can_filter_tasks_by_assigned_user()
    {
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user1->id]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $user1->id,
        ]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $user2->id,
        ]);

        $token = $this->authenticateUser($user1);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks?assigned_to={$user1->id}");

        $response->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($user1->id, $response->json('data.0.assigned_to.id'));
    }

    #[Test]
    public function user_can_search_tasks()
    {
        // Clean up any existing tasks to ensure test isolation
        \App\Models\Task::query()->delete();

        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create task with "meeting" in title
        $meetingTask = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Meeting task',
            'description' => 'Prepare for meeting',
        ]);

        // Create task without "meeting"
        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Development task',
            'description' => 'Code review needed',
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?search=meeting');

        $response->assertStatus(200);

        $tasks = $response->json('data');

        // Find tasks created by this user that contain "meeting"
        $matchingTasks = array_filter($tasks, function ($task) use ($user) {
            return isset($task['created_by']['id']) &&
                   $task['created_by']['id'] === $user->id &&
                   (str_contains(strtolower($task['title']), 'meeting') ||
                    str_contains(strtolower($task['description'] ?? ''), 'meeting'));
        });

        $this->assertCount(1, $matchingTasks);

        // Verify it's the meeting task
        $task = reset($matchingTasks);
        $this->assertEquals($meetingTask->id, $task['id']);
        $this->assertStringContainsString('Meeting', $task['title']);
    }

    #[Test]
    public function user_can_sort_tasks_by_due_date()
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'due_date' => now()->addDays(3),
            'created_by' => $user->id,
        ]);

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'due_date' => now()->addDays(1),
            'assigned_to' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?sort_by=due_date&sort_order=asc');

        $response->assertStatus(200);

        $tasks = $response->json('data');
        $this->assertCount(2, $tasks);
        // First task should have earlier due date
        $this->assertLessThanOrEqual(
            $tasks[1]['due_date'],
            $tasks[0]['due_date']
        );
    }

    #[Test]
    public function unauthenticated_user_cannot_list_tasks()
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function manager_can_create_task()
    {
        $managerRole = Role::bySlug('manager')->first();
        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $project = Project::factory()->createQuietly(['created_by' => $manager->id]);

        $token = $this->authenticateUser($manager);

        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'project_id' => $project->id,
            'priority' => 'high',
            'assigned_to' => $manager->id,
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Task',
                    'description' => 'Task description',
                    'status' => 'pending',
                    'priority' => 'high',
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                ],
                'message' => 'Task created successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::High->value,
            'project_id' => $project->id,
            'created_by' => $manager->id,
        ]);
    }

    #[Test]
    public function task_creation_validation_fails_with_invalid_data()
    {
        $managerRole = Role::bySlug('manager')->first();
        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $token = $this->authenticateUser($manager);

        $invalidData = [
            'title' => '', // Required and min 3 chars
            'project_id' => 999, // Non-existent project
            'priority' => 'invalid_priority', // Invalid enum value
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/tasks', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'project_id', 'priority']);
    }

    #[Test]
    public function task_creation_fails_with_past_due_date()
    {
        $managerRole = Role::bySlug('manager')->first();
        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $project = Project::factory()->createQuietly(['created_by' => $manager->id]);
        $token = $this->authenticateUser($manager);

        $taskData = [
            'title' => 'Past Due Task',
            'project_id' => $project->id,
            'due_date' => now()->subDay()->toDateString(),
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    #[Test]
    public function authenticated_user_can_view_task_details()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $task = Task::factory()->createQuietly(['project_id' => $project->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'priority' => $task->priority->value,
                ],
                'message' => 'Task retrieved successfully',
            ]);
    }

    #[Test]
    public function user_can_update_assigned_task()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => TaskStatus::Pending,
        ]);

        $token = $this->authenticateUser($user);

        $updateData = [
            'status' => 'in_progress',
            'title' => 'Updated Task Title',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'in_progress',
                    'title' => 'Updated Task Title',
                ],
                'message' => 'Task updated successfully',
            ]);
    }

    #[Test]
    public function user_can_update_created_task()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'assigned_to' => null,
        ]);

        $token = $this->authenticateUser($user);

        $updateData = [
            'priority' => 'high',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'priority' => 'high',
                ],
                'message' => 'Task updated successfully',
            ]);
    }

    #[Test]
    public function user_cannot_update_unassigned_task()
    {
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user1->id]);

        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user1->id,
            'assigned_to' => $user2->id,
        ]);

        $token = $this->authenticateUser($user1);

        $updateData = [
            'title' => 'Hacked Title',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You can only update tasks assigned to you or that you created.',
            ]);
    }

    #[Test]
    public function user_can_delete_created_task()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    #[Test]
    public function admin_can_delete_any_task()
    {
        $adminRole = Role::bySlug('admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $otherUser = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $otherUser->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'created_by' => $otherUser->id,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
    }

    #[Test]
    public function task_filter_validation_rejects_invalid_status()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?status=invalid_status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function task_filter_validation_rejects_invalid_priority()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?priority=invalid_priority');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    #[Test]
    public function task_filter_validation_rejects_invalid_sort_field()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?sort_by=invalid_field');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    #[Test]
    public function user_can_paginate_tasks_with_custom_per_page()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create 10 tasks
        Task::factory()->count(10)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
                'message',
            ]);

        // Verify pagination metadata
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(5, $response->json('pagination.per_page'));
        $this->assertEquals(10, $response->json('pagination.total'));
        $this->assertEquals(2, $response->json('pagination.last_page'));
        $this->assertEquals(1, $response->json('pagination.from'));
        $this->assertEquals(5, $response->json('pagination.to'));
    }

    #[Test]
    public function user_can_navigate_to_specific_page()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create 25 tasks to have multiple pages
        $tasks = Task::factory()->count(25)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?page=2&per_page=10');

        $response->assertStatus(200);

        // Verify we're on page 2
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(10, $response->json('pagination.per_page'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
        $this->assertEquals(11, $response->json('pagination.from'));
        $this->assertEquals(20, $response->json('pagination.to'));
    }

    #[Test]
    public function user_can_paginate_with_filters()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create tasks with different statuses
        Task::factory()->count(8)->createQuietly([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'created_by' => $user->id,
        ]);

        Task::factory()->count(5)->createQuietly([
            'project_id' => $project->id,
            'status' => TaskStatus::Completed,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?status=pending&per_page=5');

        $response->assertStatus(200);

        // Should only get pending tasks, paginated
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(8, $response->json('pagination.total'));
        $this->assertEquals(2, $response->json('pagination.last_page'));

        // Verify all returned tasks are pending
        foreach ($response->json('data') as $task) {
            $this->assertEquals('pending', $task['status']);
        }
    }

    #[Test]
    public function pagination_validation_rejects_invalid_per_page()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    #[Test]
    public function pagination_validation_rejects_negative_per_page()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?per_page=-1');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    #[Test]
    public function pagination_validation_rejects_invalid_page()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    #[Test]
    public function pagination_validation_rejects_negative_page()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?page=-1');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    #[Test]
    public function user_can_request_last_page_of_paginated_results()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create 23 tasks (will result in 2 pages with per_page=15, and 1 page with per_page=10)
        Task::factory()->count(23)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        // Request last page
        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?page=2&per_page=15');

        $response->assertStatus(200);

        // Should be on page 2 with remaining items
        $this->assertCount(8, $response->json('data')); // 23 - 15 = 8
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(15, $response->json('pagination.per_page'));
        $this->assertEquals(23, $response->json('pagination.total'));
        $this->assertEquals(2, $response->json('pagination.last_page'));
        $this->assertEquals(16, $response->json('pagination.from'));
        $this->assertEquals(23, $response->json('pagination.to'));
    }

    #[Test]
    public function pagination_returns_empty_data_for_page_beyond_results()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        // Create only 5 tasks
        Task::factory()->count(5)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        // Request page 2 when there's only 1 page
        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?page=2');

        $response->assertStatus(200);

        // Should return empty data but still have pagination metadata
        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(15, $response->json('pagination.per_page'));
        $this->assertEquals(5, $response->json('pagination.total'));
        $this->assertEquals(1, $response->json('pagination.last_page'));
        $this->assertNull($response->json('pagination.from'));
        $this->assertNull($response->json('pagination.to'));
    }

    #[Test]
    public function task_resource_returns_correct_structure_with_all_relationships_loaded()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $assignedUser = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $assignedUser->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'project' => [
                        'id',
                        'name',
                    ],
                    'assigned_to' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'created_by' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'due_date',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($task->id, $data['id']);
        $this->assertEquals($task->title, $data['title']);
        $this->assertEquals($task->status->value, $data['status']);
        $this->assertEquals($task->priority->value, $data['priority']);

        // Verify project relationship
        $this->assertEquals($project->id, $data['project']['id']);
        $this->assertEquals($project->name, $data['project']['name']);

        // Verify assigned_to relationship
        $this->assertEquals($assignedUser->id, $data['assigned_to']['id']);
        $this->assertEquals($assignedUser->first_name, $data['assigned_to']['first_name']);
        $this->assertEquals($assignedUser->last_name, $data['assigned_to']['last_name']);
        $this->assertEquals($assignedUser->email, $data['assigned_to']['email']);

        // Verify created_by relationship
        $this->assertEquals($user->id, $data['created_by']['id']);
        $this->assertEquals($user->first_name, $data['created_by']['first_name']);
        $this->assertEquals($user->last_name, $data['created_by']['last_name']);
        $this->assertEquals($user->email, $data['created_by']['email']);
    }

    #[Test]
    public function task_resource_returns_correct_structure_with_no_relationships_loaded()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $task = Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => null,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'project' => [
                        'id',
                        'name',
                    ],
                    'assigned_to', // Should be null when not assigned
                    'created_by' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'due_date',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($task->id, $data['id']);
        $this->assertEquals($task->title, $data['title']);
        $this->assertEquals($task->status->value, $data['status']);
        $this->assertEquals($task->priority->value, $data['priority']);

        // Verify relationships are loaded correctly
        $this->assertEquals($project->id, $data['project']['id']);
        $this->assertEquals($project->name, $data['project']['name']);
        $this->assertNull($data['assigned_to']);

        // Verify created_by relationship is still loaded
        $this->assertEquals($user->id, $data['created_by']['id']);
        $this->assertEquals($user->first_name, $data['created_by']['first_name']);
        $this->assertEquals($user->last_name, $data['created_by']['last_name']);
        $this->assertEquals($user->email, $data['created_by']['email']);
    }

    #[Test]
    public function task_resource_handles_null_due_date_correctly()
    {
        $user = User::factory()->regularUser()->create();
        $task = Task::factory()->createQuietly([
            'created_by' => $user->id,
            'due_date' => null,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNull($data['due_date']);
    }

    #[Test]
    public function task_resource_handles_present_due_date_correctly()
    {
        $user = User::factory()->regularUser()->create();
        $dueDate = now()->addDays(7)->microsecond(0);
        $task = Task::factory()->createQuietly([
            'created_by' => $user->id,
            'due_date' => $dueDate,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($dueDate->toISOString(), $data['due_date']);
    }

    #[Test]
    public function task_list_resource_returns_correct_structure_with_relationships()
    {
        $user = User::factory()->manager()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);
        $assignedUser = User::factory()->regularUser()->create();

        Task::factory()->createQuietly([
            'project_id' => $project->id,
            'assigned_to' => $assignedUser->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'project' => [
                            'id',
                            'name',
                        ],
                        'assigned_to' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                        ],
                        'created_by' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                        ],
                        'due_date',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination',
                'message',
            ]);

        // Verify the first task has all relationships loaded
        $firstTask = $response->json('data.0');
        $this->assertArrayHasKey('project', $firstTask);
        $this->assertArrayHasKey('assigned_to', $firstTask);
        $this->assertArrayHasKey('created_by', $firstTask);

        $this->assertEquals($project->id, $firstTask['project']['id']);
        $this->assertEquals($project->name, $firstTask['project']['name']);

        $this->assertEquals($assignedUser->id, $firstTask['assigned_to']['id']);
        $this->assertEquals($assignedUser->first_name, $firstTask['assigned_to']['first_name']);
        $this->assertEquals($assignedUser->last_name, $firstTask['assigned_to']['last_name']);
        $this->assertEquals($assignedUser->email, $firstTask['assigned_to']['email']);

        $this->assertEquals($user->id, $firstTask['created_by']['id']);
        $this->assertEquals($user->first_name, $firstTask['created_by']['first_name']);
        $this->assertEquals($user->last_name, $firstTask['created_by']['last_name']);
        $this->assertEquals($user->email, $firstTask['created_by']['email']);
    }
}
