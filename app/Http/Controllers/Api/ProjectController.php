<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Project\ProjectDTO;
use App\Enums\Project\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateProjectRequest;
use App\Http\Requests\Api\ProjectIndexRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Responses\JsonResponse;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Get list of projects with optional status filtering
     */
    public function index(ProjectIndexRequest $request): LaravelJsonResponse
    {
        try {
            $status = $request->validated('status', null);

            if (is_null($status)) {
                $projects = $this->projectRepository->getActiveProjects()->load(['createdBy', 'tasks']);
            } else {
                $projects = $this->projectRepository->getByStatus(ProjectStatus::from($status))->load(['createdBy', 'tasks']);
            }

            return JsonResponse::success(ProjectResource::collection($projects), 'Projects retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve projects');
        }
    }

    /**
     * Create a new project (manager, admin only)
     */
    public function store(CreateProjectRequest $request): LaravelJsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $this->authService->user();

            $projectData = array_merge($request->validated(), [
                'created_by' => $user->id,
                'status' => ProjectStatus::Active,
            ]);

            $projectDTO = ProjectDTO::fromArray($projectData);
            $project = $this->projectRepository->createFromDTO($projectDTO);

            // Load the createdBy relationship for the response
            $project->load('createdBy');

            return JsonResponse::created(new ProjectResource($project), 'Project created successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to create project');
        }
    }

    /**
     * Get project details
     */
    public function show(int $id): LaravelJsonResponse
    {
        try {
            $project = $this->projectRepository->findById($id);

            if (! $project) {
                return JsonResponse::notFound('Project not found');
            }

            // Load relationships for the response
            $project->load(['createdBy', 'tasks']);

            return JsonResponse::success(new ProjectResource($project), 'Project retrieved successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to retrieve project');
        }
    }

    /**
     * Update project (author or admin)
     */
    public function update(UpdateProjectRequest $request, int $id): LaravelJsonResponse
    {
        try {
            $user = $this->authService->user();
            $project = $this->projectRepository->findById($id);

            if (! $user) {
                return JsonResponse::unauthorized('User not authenticated');
            }

            if (! $project) {
                return JsonResponse::notFound('Project not found');
            }

            // Check permissions: author or admin
            if ($project->created_by !== $user->id && $user->role?->slug !== 'admin') {
                return JsonResponse::forbidden('You can only update your own projects');
            }

            // Get current project data for merging
            $currentData = [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'created_by' => $project->created_by,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ];

            $mergedData = array_merge($currentData, $request->validated());

            $projectDTO = ProjectDTO::fromArray($mergedData);
            $updated = $this->projectRepository->updateFromDTO($id, $projectDTO);

            if (! $updated) {
                return JsonResponse::internalServerError('Failed to update project');
            }

            $updatedProject = $this->projectRepository->findById($id);

            // Load relationships for the response
            $updatedProject->load(['createdBy', 'tasks']);

            return JsonResponse::success(new ProjectResource($updatedProject), 'Project updated successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to update project');
        }
    }

    /**
     * Delete project (author or admin)
     */
    public function destroy(int $id): LaravelJsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $this->authService->user();
            $project = $this->projectRepository->findById($id);

            if (! $project) {
                return JsonResponse::notFound('Project not found');
            }

            /** @var \App\Models\Role $role */
            $role = $user->role;
            // Check permissions: author or admin
            if ($project->created_by !== $user->id && $role->slug !== 'admin') {
                return JsonResponse::forbidden('You can only delete your own projects');
            }

            // Note: Laravel repositories typically don't have delete methods
            // We'll use the model directly for deletion
            $deleted = $project->delete();

            if (! $deleted) {
                return JsonResponse::internalServerError('Failed to delete project');
            }

            return JsonResponse::successMessage('Project deleted successfully');
        } catch (\Exception $e) {
            return JsonResponse::internalServerError('Failed to delete project');
        }
    }
}
