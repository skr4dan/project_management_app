<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Project\ProjectDTO;
use App\Enums\Project\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateProjectRequest;
use App\Http\Requests\Api\ProjectIndexRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
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
    public function index(ProjectIndexRequest $request): JsonResponse
    {
        try {
            $status = $request->validated('status', null);

            if (is_null($status)) {
                $projects = $this->projectRepository->getActiveProjects();
            } else {
                $projects = $this->projectRepository->getByStatus(ProjectStatus::from($status));
            }

            return response()->json([
                'success' => true,
                'data' => ProjectResource::collection($projects),
                'message' => 'Projects retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve projects',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new project (manager, admin only)
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->user();

            $projectData = array_merge($request->validated(), [
                'created_by' => $user->id,
                'status' => ProjectStatus::Active,
            ]);

            $projectDTO = ProjectDTO::fromArray($projectData);
            $project = $this->projectRepository->createFromDTO($projectDTO);

            // Load the createdBy relationship for the response
            $project->load('createdBy');

            return response()->json([
                'success' => true,
                'data' => new ProjectResource($project),
                'message' => 'Project created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get project details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $project = $this->projectRepository->findById($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => new ProjectResource($project),
                'message' => 'Project retrieved successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve project',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update project (author or admin)
     */
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->authService->user();
            $project = $this->projectRepository->findById($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check permissions: author or admin
            if ($project->created_by !== $user->id && $user->role->slug !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own projects',
                ], Response::HTTP_FORBIDDEN);
            }

            // Prepare update data
            $updateData = array_filter($request->validated(), function($value) {
                return $value !== null;
            });

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

            $mergedData = array_merge($currentData, $updateData);

            $projectDTO = ProjectDTO::fromArray($mergedData);
            $updated = $this->projectRepository->updateFromDTO($id, $projectDTO);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update project',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $updatedProject = $this->projectRepository->findById($id);

            return response()->json([
                'success' => true,
                'data' => new ProjectResource($updatedProject),
                'message' => 'Project updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete project (author or admin)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = $this->authService->user();
            $project = $this->projectRepository->findById($id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check permissions: author or admin
            if ($project->created_by !== $user->id && $user->role->slug !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own projects',
                ], Response::HTTP_FORBIDDEN);
            }

            // Note: Laravel repositories typically don't have delete methods
            // We'll use the model directly for deletion
            $deleted = $project->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete project',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
