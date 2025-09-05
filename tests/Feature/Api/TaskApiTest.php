<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Role;
use App\Models\Project;
use App\Models\Task;
use App\Enums\Task\TaskStatus;
use App\Enums\Task\TaskPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_list_tasks()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);

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
                'message',
            ]);
    }

    #[Test]
    public function user_can_filter_tasks_by_status()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->create([
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
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => TaskPriority::High,
            'created_by' => $user->id,
        ]);

        Task::factory()->create([
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
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['created_by' => $user->id]);
        $project2 = Project::factory()->create(['created_by' => $user->id]);

        Task::factory()->create(['project_id' => $project1->id, 'created_by' => $user->id]);
        Task::factory()->create(['project_id' => $project2->id, 'assigned_to' => $user->id]);

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
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user1->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user1->id,
        ]);

        Task::factory()->create([
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

        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        // Create task with "meeting" in title
        $meetingTask = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Meeting task',
            'description' => 'Prepare for meeting',
        ]);

        // Create task without "meeting"
        Task::factory()->create([
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
        $project = Project::factory()->create(['created_by' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(3),
            'created_by' => $user->id,
        ]);

        Task::factory()->create([
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

        $project = Project::factory()->create(['created_by' => $manager->id]);

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

        $project = Project::factory()->create(['created_by' => $manager->id]);
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
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

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
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $task = Task::factory()->create([
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
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $task = Task::factory()->create([
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
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user1->id]);

        $task = Task::factory()->create([
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
                'message' => 'You can only update tasks assigned to you or created by you',
            ]);
    }

    #[Test]
    public function user_can_delete_created_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $task = Task::factory()->create([
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

        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $otherUser->id]);
        $task = Task::factory()->create([
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
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?status=invalid_status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function task_filter_validation_rejects_invalid_priority()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?priority=invalid_priority');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    #[Test]
    public function task_filter_validation_rejects_invalid_sort_field()
    {
        $user = User::factory()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/tasks?sort_by=invalid_field');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }
}
