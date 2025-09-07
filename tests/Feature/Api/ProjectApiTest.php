<?php

namespace Tests\Feature\Api;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_list_projects()
    {
        $user = User::factory()->regularUser()->create();
        $projects = Project::factory()->count(3)->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active->value,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Projects retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'created_by',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function user_can_filter_projects_by_status()
    {
        $user = User::factory()->regularUser()->create();

        Project::factory()->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active,
        ]);

        Project::factory()->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Completed,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/projects?status=active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active', $response->json('data.0.status'));
    }

    #[Test]
    public function unauthenticated_user_cannot_list_projects()
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function manager_can_create_project()
    {
        $managerRole = Role::bySlug('manager')->first();
        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $token = $this->authenticateUser($manager);

        $projectData = [
            'name' => 'New Project',
            'description' => 'Project description',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Project',
                    'description' => 'Project description',
                    'status' => 'active',
                    'created_by' => [
                        'id' => $manager->id,
                        'first_name' => $manager->first_name,
                        'last_name' => $manager->last_name,
                    ],
                ],
                'message' => 'Project created successfully',
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'description' => 'Project description',
            'status' => ProjectStatus::Active->value,
            'created_by' => $manager->id,
        ]);
    }

    #[Test]
    public function admin_can_create_project()
    {
        $adminRole = Role::bySlug('admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $token = $this->authenticateUser($admin);

        $projectData = [
            'name' => 'Admin Project',
            'description' => 'Admin created project',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Project created successfully',
            ]);
    }

    #[Test]
    public function regular_user_cannot_create_project()
    {
        $userRole = Role::bySlug('user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $token = $this->authenticateUser($user);

        $projectData = [
            'name' => 'User Project',
            'description' => 'User created project',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/projects', $projectData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient permissions',
            ]);
    }

    #[Test]
    public function project_creation_validation_fails_with_invalid_data()
    {
        $managerRole = Role::bySlug('manager')->first();
        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $token = $this->authenticateUser($manager);

        $invalidData = [
            'name' => '', // Required and min 3 chars
            'description' => str_repeat('a', 1001), // Max 1000 chars
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->postJson('/api/projects', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description']);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_project()
    {
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test description',
        ];

        $response = $this->postJson('/api/projects', $projectData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token is invalid or expired',
            ]);
    }

    #[Test]
    public function authenticated_user_can_view_project_details()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status->value,
                ],
                'message' => 'Project retrieved successfully',
            ]);
    }

    #[Test]
    public function user_cannot_view_nonexistent_project()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/projects/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function project_creator_can_update_project()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $user->id,
            'name' => 'Original Name',
        ]);

        $token = $this->authenticateUser($user);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => 'Updated Name',
                    'description' => 'Updated description',
                ],
                'message' => 'Project updated successfully',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function admin_can_update_any_project()
    {
        $adminRole = Role::bySlug('admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $otherUser = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $otherUser->id,
            'name' => 'Original Name',
        ]);

        $token = $this->authenticateUser($admin);

        $updateData = [
            'name' => 'Admin Updated',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Admin Updated',
                ],
                'message' => 'Project updated successfully',
            ]);
    }

    #[Test]
    public function user_cannot_update_other_user_project()
    {
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();

        $project = Project::factory()->createQuietly([
            'created_by' => $user2->id,
        ]);

        $token = $this->authenticateUser($user1);

        $updateData = [
            'name' => 'Hacked Name',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You can only update your own projects',
            ]);
    }

    #[Test]
    public function project_update_validation_fails_with_invalid_status()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        $token = $this->authenticateUser($user);

        $invalidData = [
            'status' => 'invalid_status',
        ];

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->putJson("/api/projects/{$project->id}", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function project_creator_can_delete_project()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    #[Test]
    public function admin_can_delete_any_project()
    {
        $adminRole = Role::bySlug('admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $otherUser = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $otherUser->id]);

        $token = $this->authenticateUser($admin);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);
    }

    #[Test]
    public function user_cannot_delete_other_user_project()
    {
        $user1 = User::factory()->regularUser()->create();
        $user2 = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user2->id]);

        $token = $this->authenticateUser($user1);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You can only delete your own projects',
            ]);
    }

    #[Test]
    public function user_cannot_delete_nonexistent_project()
    {
        $user = User::factory()->regularUser()->create();
        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->deleteJson('/api/projects/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function project_resource_returns_correct_structure_with_relationships_loaded()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active,
        ]);

        // Create some tasks for the project
        \App\Models\Task::factory()->count(3)->createQuietly([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'created_by' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'tasks_count',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($project->id, $data['id']);
        $this->assertEquals($project->name, $data['name']);
        $this->assertEquals($project->description, $data['description']);
        $this->assertEquals($project->status->value, $data['status']);

        // Verify created_by relationship
        $this->assertEquals($user->id, $data['created_by']['id']);
        $this->assertEquals($user->first_name, $data['created_by']['first_name']);
        $this->assertEquals($user->last_name, $data['created_by']['last_name']);
        $this->assertEquals($user->email, $data['created_by']['email']);

        // Verify tasks_count
        $this->assertEquals(3, $data['tasks_count']);
    }

    #[Test]
    public function project_resource_returns_correct_structure_with_no_relationships_loaded()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly(['created_by' => $user->id]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'created_by' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'tasks_count',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify the actual data structure
        $data = $response->json('data');
        $this->assertEquals($project->id, $data['id']);
        $this->assertEquals($project->name, $data['name']);
        $this->assertEquals($project->description, $data['description']);
        $this->assertEquals($project->status->value, $data['status']);

        // Verify created_by relationship is loaded
        $this->assertEquals($user->id, $data['created_by']['id']);
        $this->assertEquals($user->first_name, $data['created_by']['first_name']);
        $this->assertEquals($user->last_name, $data['created_by']['last_name']);
        $this->assertEquals($user->email, $data['created_by']['email']);

        // Verify tasks_count is available (but 0 since no tasks loaded)
        $this->assertEquals(0, $data['tasks_count']);
    }

    #[Test]
    public function project_resource_handles_null_description_correctly()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $user->id,
            'description' => null,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNull($data['description']);
    }

    #[Test]
    public function project_resource_handles_present_description_correctly()
    {
        $user = User::factory()->regularUser()->create();
        $project = Project::factory()->createQuietly([
            'created_by' => $user->id,
            'description' => 'This is a test project description',
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('This is a test project description', $data['description']);
    }

    #[Test]
    public function project_list_resource_returns_correct_structure_with_creator()
    {
        $user = User::factory()->regularUser()->create();
        $projects = Project::factory()->count(2)->createQuietly([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active,
        ]);

        $token = $this->authenticateUser($user);

        $response = $this->withHeaders($this->getAuthHeader($token))
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'created_by' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                        ],
                        'tasks_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        // Verify the first project has creator loaded
        $firstProject = $response->json('data.0');
        $this->assertArrayHasKey('created_by', $firstProject);
        $this->assertIsArray($firstProject['created_by']);
        $this->assertEquals($user->id, $firstProject['created_by']['id']);
        $this->assertEquals($user->first_name, $firstProject['created_by']['first_name']);
        $this->assertEquals($user->last_name, $firstProject['created_by']['last_name']);
        $this->assertEquals($user->email, $firstProject['created_by']['email']);

        // Verify tasks_count is included
        $this->assertArrayHasKey('tasks_count', $firstProject);
    }
}
