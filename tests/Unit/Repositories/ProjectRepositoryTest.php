<?php

namespace Tests\Unit\Repositories;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRepository = new ProjectRepository(new \App\Models\Project());
    }

    #[Test]
    public function it_can_find_project_by_id()
    {
        $project = Project::factory()->create();

        $foundProject = $this->projectRepository->findById($project->id);

        $this->assertInstanceOf(Project::class, $foundProject);
        $this->assertEquals($project->id, $foundProject->id);
    }

    #[Test]
    public function it_returns_null_when_project_not_found_by_id()
    {
        $foundProject = $this->projectRepository->findById(999);

        $this->assertNull($foundProject);
    }

    #[Test]
    public function it_can_find_projects_by_owner()
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->create(['created_by' => $user->id]);
        Project::factory()->count(1)->create(); // Different owner

        $projects = $this->projectRepository->findByOwner($user->id);

        $this->assertCount(2, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
        $this->assertContainsOnlyInstancesOf(Project::class, $projects);
        $projects->each(function ($project) use ($user) {
            $this->assertEquals($user->id, $project->created_by);
        });
    }

    #[Test]
    public function it_returns_empty_collection_when_no_projects_found_by_owner()
    {
        $user = User::factory()->create();

        $projects = $this->projectRepository->findByOwner($user->id);

        $this->assertCount(0, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
    }

    #[Test]
    public function it_can_find_projects_by_status()
    {
        Project::factory()->count(2)->create(['status' => ProjectStatus::Active->value]);
        Project::factory()->count(1)->create(['status' => ProjectStatus::Completed->value]);

        $projects = $this->projectRepository->findByStatus(ProjectStatus::Active);

        $this->assertCount(2, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
        $projects->each(function ($project) {
            $this->assertEquals(ProjectStatus::Active, $project->status);
        });
    }

    #[Test]
    public function it_can_get_active_projects()
    {
        Project::factory()->count(3)->create(['status' => ProjectStatus::Active->value]);
        Project::factory()->count(2)->create(['status' => ProjectStatus::Completed->value]);

        $projects = $this->projectRepository->getActiveProjects();

        $this->assertCount(3, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
        $projects->each(function ($project) {
            $this->assertEquals(ProjectStatus::Active, $project->status);
        });
    }

    #[Test]
    public function it_can_get_completed_projects()
    {
        Project::factory()->count(2)->create(['status' => ProjectStatus::Completed->value]);
        Project::factory()->count(3)->create(['status' => ProjectStatus::Active->value]);

        $projects = $this->projectRepository->getCompletedProjects();

        $this->assertCount(2, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
        $projects->each(function ($project) {
            $this->assertEquals(ProjectStatus::Completed, $project->status);
        });
    }

    #[Test]
    public function it_can_create_project_from_dto()
    {
        $user = User::factory()->create();
        $projectDTO = new \App\DTOs\Project\ProjectDTO(
            id: null,
            name: 'Test Project',
            description: 'Test Description',
            status: ProjectStatus::Active,
            created_by: $user->id,
            created_at: null,
            updated_at: null
        );

        $project = $this->projectRepository->createFromDTO($projectDTO);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('Test Project', $project->name);
        $this->assertEquals('Test Description', $project->description);
        $this->assertEquals(ProjectStatus::Active, $project->status);
        $this->assertEquals($user->id, $project->created_by);
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => ProjectStatus::Active->value,
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_can_update_project_from_dto()
    {
        $project = Project::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'status' => ProjectStatus::Active->value,
        ]);

        $projectDTO = new \App\DTOs\Project\ProjectDTO(
            id: $project->id,
            name: 'Updated Name',
            description: 'Updated Description',
            status: ProjectStatus::Completed,
            created_by: $project->created_by,
            created_at: $project->created_at,
            updated_at: $project->updated_at
        );

        $updated = $this->projectRepository->updateFromDTO($project->id, $projectDTO);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $project->fresh()->name);
        $this->assertEquals('Updated Description', $project->fresh()->description);
        $this->assertEquals(ProjectStatus::Completed, $project->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_update_from_dto_fails()
    {
        $projectDTO = new \App\DTOs\Project\ProjectDTO(
            id: null,
            name: 'Test Project',
            description: 'Test Description',
            status: ProjectStatus::Active,
            created_by: 1,
            created_at: null,
            updated_at: null
        );

        $updated = $this->projectRepository->updateFromDTO(999, $projectDTO);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_update_project_status()
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Active->value]);

        $updated = $this->projectRepository->updateStatus($project->id, ProjectStatus::Completed);

        $this->assertTrue($updated);
        $this->assertEquals(ProjectStatus::Completed, $project->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_update_status_fails()
    {
        $updated = $this->projectRepository->updateStatus(999, ProjectStatus::Completed);

        $this->assertFalse($updated);
    }

    #[Test]
    public function it_can_get_project_with_tasks_count()
    {
        $project = Project::factory()->create();
        Task::factory()->count(3)->create(['project_id' => $project->id]);

        $result = $this->projectRepository->getWithTasksCount($project->id);

        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals($project->id, $result->id);
        // Note: This would typically test the loaded relationship count
    }

    #[Test]
    public function it_returns_null_when_get_with_tasks_count_fails()
    {
        $result = $this->projectRepository->getWithTasksCount(999);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_get_projects_with_statistics()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(2)->create(['created_by' => $user->id]);
        Task::factory()->count(5)->create(['project_id' => $projects->first()->id]);

        $results = $this->projectRepository->getWithStatistics($user->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_can_get_projects_with_statistics_for_all_users()
    {
        Project::factory()->count(3)->create();

        $results = $this->projectRepository->getWithStatistics();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        $this->assertCount(3, $results);
    }

    #[Test]
    public function it_can_get_completion_percentage()
    {
        $project = Project::factory()->create();
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'status' => 'completed'
        ]);
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'status' => 'pending'
        ]);

        $percentage = $this->projectRepository->getCompletionPercentage($project->id);

        $this->assertEquals(40.0, $percentage); // 2 out of 5 tasks completed
    }

    #[Test]
    public function it_returns_zero_completion_percentage_for_project_with_no_tasks()
    {
        $project = Project::factory()->create();

        $percentage = $this->projectRepository->getCompletionPercentage($project->id);

        $this->assertEquals(0.0, $percentage);
    }

    #[Test]
    public function it_can_search_projects()
    {
        $user = User::factory()->create();
        Project::factory()->create([
            'name' => 'Laravel Project',
            'created_by' => $user->id
        ]);
        Project::factory()->create([
            'name' => 'React Project',
            'created_by' => $user->id
        ]);
        Project::factory()->create(['name' => 'Vue Project']); // Different user

        $results = $this->projectRepository->search('Laravel', $user->id);

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Project', $results->first()->name);
    }

    #[Test]
    public function it_can_search_projects_without_user_filter()
    {
        Project::factory()->create(['name' => 'Laravel Project']);
        Project::factory()->create(['name' => 'React Project']);

        $results = $this->projectRepository->search('Laravel');

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Project', $results->first()->name);
    }

    #[Test]
    public function it_returns_empty_collection_when_search_finds_no_results()
    {
        Project::factory()->create(['name' => 'Laravel Project']);

        $results = $this->projectRepository->search('Non-existent Project');

        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_can_get_user_project_statistics()
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->create([
            'created_by' => $user->id,
            'status' => ProjectStatus::Active->value
        ]);
        Project::factory()->count(1)->create([
            'created_by' => $user->id,
            'status' => ProjectStatus::Completed->value
        ]);

        $stats = $this->projectRepository->getUserProjectStatistics($user->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(1, $stats['completed']);
    }

    #[Test]
    public function it_can_archive_project()
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Active->value]);

        $archived = $this->projectRepository->archive($project->id);

        $this->assertTrue($archived);
        $this->assertEquals(ProjectStatus::Archived, $project->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_archive_fails()
    {
        $archived = $this->projectRepository->archive(999);

        $this->assertFalse($archived);
    }

    #[Test]
    public function it_can_complete_project()
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Active->value]);

        $completed = $this->projectRepository->complete($project->id);

        $this->assertTrue($completed);
        $this->assertEquals(ProjectStatus::Completed, $project->fresh()->status);
    }

    #[Test]
    public function it_returns_false_when_complete_fails()
    {
        $completed = $this->projectRepository->complete(999);

        $this->assertFalse($completed);
    }
}
