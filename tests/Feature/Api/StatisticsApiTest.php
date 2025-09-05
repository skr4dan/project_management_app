<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_access_statistics()
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create projects
        $project1 = Project::factory()->create(['created_by' => $admin->id]);
        $project2 = Project::factory()->create(['created_by' => $user1->id]);

        // Create tasks with different statuses
        Task::factory()->create([
            'project_id' => $project1->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::Completed,
        ]);

        Task::factory()->create([
            'project_id' => $project1->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::Completed,
        ]);

        Task::factory()->create([
            'project_id' => $project1->id,
            'created_by' => $user1->id,
            'status' => \App\Enums\Task\TaskStatus::InProgress,
        ]);

        Task::factory()->create([
            'project_id' => $project2->id,
            'created_by' => $user2->id,
            'status' => \App\Enums\Task\TaskStatus::Pending,
        ]);

        // Create additional tasks for top active users
        Task::factory()->create([
            'project_id' => $project1->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::Completed,
        ]);

        Task::factory()->create([
            'project_id' => $project2->id,
            'created_by' => $user1->id,
            'status' => \App\Enums\Task\TaskStatus::InProgress,
        ]);

        // Create overdue task
        Task::factory()->create([
            'project_id' => $project1->id,
            'created_by' => $admin->id,
            'due_date' => now()->subDay(),
            'status' => \App\Enums\Task\TaskStatus::Pending,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_projects',
                    'total_tasks',
                    'tasks_by_status',
                    'overdue_tasks',
                    'top_active_users' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'task_count',
                        ],
                    ],
                ],
                'message',
            ]);

        $data = $response->json('data');

        // Verify exact numbers
        $this->assertEquals(2, $data['total_projects']);
        $this->assertEquals(7, $data['total_tasks']); // 2 completed + 1 in_progress + 1 pending + 1 additional + 1 overdue + 1 more = 7

        // Verify overdue tasks (exact count)
        $this->assertEquals(1, $data['overdue_tasks']);

        // Verify tasks by status contains expected statuses
        $this->assertArrayHasKey('completed', $data['tasks_by_status']);
        $this->assertArrayHasKey('in_progress', $data['tasks_by_status']);
        $this->assertArrayHasKey('pending', $data['tasks_by_status']);

        // Verify exact status counts
        $this->assertEquals(3, $data['tasks_by_status']['completed']); // 2 completed + 1 additional completed
        $this->assertEquals(2, $data['tasks_by_status']['in_progress']); // 1 in_progress + 1 additional in_progress
        $this->assertEquals(2, $data['tasks_by_status']['pending']); // 1 pending + 1 overdue

        // Verify top active users (exact counts)
        $this->assertCount(3, $data['top_active_users']);
        $this->assertEquals($admin->first_name . ' ' . $admin->last_name, $data['top_active_users'][0]['name']);
        $this->assertEquals(4, $data['top_active_users'][0]['task_count']); // 2 completed + 1 additional + 1 overdue
        $this->assertEquals(2, $data['top_active_users'][1]['task_count']); // 1 in_progress + 1 additional
        $this->assertEquals(1, $data['top_active_users'][2]['task_count']); // 1 pending
    }

    #[Test]
    public function non_admin_cannot_access_statistics()
    {
        $user = User::factory()->create(); // Regular user
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function unauthenticated_user_cannot_access_statistics()
    {
        $response = $this->getJson('/api/statistics');

        $response->assertStatus(401); // Unauthorized
    }

    #[Test]
    public function statistics_with_no_data_returns_zeros()
    {
        $admin = User::factory()->admin()->create();
        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify exact zero counts when no data exists
        $this->assertEquals(0, $data['total_projects']);
        $this->assertEquals(0, $data['total_tasks']);
        $this->assertEquals(0, $data['overdue_tasks']);
        $this->assertEmpty($data['top_active_users']);

        // Verify status counts are zero (handle case where keys might not exist)
        $this->assertEquals(0, $data['tasks_by_status']['completed'] ?? 0);
        $this->assertEquals(0, $data['tasks_by_status']['in_progress'] ?? 0);
        $this->assertEquals(0, $data['tasks_by_status']['pending'] ?? 0);
    }

    #[Test]
    public function statistics_overdue_tasks_count_exact()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['created_by' => $admin->id]);

        // Create 2 overdue tasks
        Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'due_date' => now()->subDay(),
            'status' => \App\Enums\Task\TaskStatus::Pending,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'due_date' => now()->subDays(2),
            'status' => \App\Enums\Task\TaskStatus::InProgress,
        ]);

        // Create 1 non-overdue task
        Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'due_date' => now()->addDay(),
            'status' => \App\Enums\Task\TaskStatus::Pending,
        ]);

        // Create 1 completed overdue task (should not count as overdue)
        Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'due_date' => now()->subDay(),
            'status' => \App\Enums\Task\TaskStatus::Completed,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should count only 2 overdue tasks (not completed ones)
        $this->assertEquals(2, $data['overdue_tasks']);
        $this->assertEquals(4, $data['total_tasks']); // 2 overdue + 1 future + 1 completed overdue
    }

    #[Test]
    public function statistics_top_users_ranking_exact()
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $project = Project::factory()->create(['created_by' => $admin->id]);

        // Create tasks for each user with specific counts
        // User 3: 5 tasks
        Task::factory()->count(5)->create([
            'project_id' => $project->id,
            'created_by' => $user3->id,
        ]);

        // User 1: 4 tasks
        Task::factory()->count(4)->create([
            'project_id' => $project->id,
            'created_by' => $user1->id,
        ]);

        // User 2: 3 tasks
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'created_by' => $user2->id,
        ]);

        // Admin: 2 tasks
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify exact ranking
        $this->assertCount(4, $data['top_active_users']);

        // User 3 should be first (5 tasks)
        $this->assertEquals($user3->first_name . ' ' . $user3->last_name, $data['top_active_users'][0]['name']);
        $this->assertEquals(5, $data['top_active_users'][0]['task_count']);

        // User 1 should be second (4 tasks)
        $this->assertEquals($user1->first_name . ' ' . $user1->last_name, $data['top_active_users'][1]['name']);
        $this->assertEquals(4, $data['top_active_users'][1]['task_count']);

        // User 2 should be third (3 tasks)
        $this->assertEquals($user2->first_name . ' ' . $user2->last_name, $data['top_active_users'][2]['name']);
        $this->assertEquals(3, $data['top_active_users'][2]['task_count']);

        // Admin should be fourth (2 tasks)
        $this->assertEquals($admin->first_name . ' ' . $admin->last_name, $data['top_active_users'][3]['name']);
        $this->assertEquals(2, $data['top_active_users'][3]['task_count']);
    }

    #[Test]
    public function statistics_task_status_distribution_exact()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['created_by' => $admin->id]);

        // Create specific number of tasks for each status
        Task::factory()->count(5)->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::Completed,
        ]);

        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::InProgress,
        ]);

        Task::factory()->count(7)->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\Task\TaskStatus::Pending,
        ]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/statistics');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify exact totals
        $this->assertEquals(1, $data['total_projects']);
        $this->assertEquals(15, $data['total_tasks']); // 5 + 3 + 7

        // Verify exact status distribution
        $this->assertEquals(5, $data['tasks_by_status']['completed']);
        $this->assertEquals(3, $data['tasks_by_status']['in_progress']);
        $this->assertEquals(7, $data['tasks_by_status']['pending']);

        // Verify top user
        $this->assertCount(1, $data['top_active_users']);
        $this->assertEquals($admin->first_name . ' ' . $admin->last_name, $data['top_active_users'][0]['name']);
        $this->assertEquals(15, $data['top_active_users'][0]['task_count']);
    }
}
