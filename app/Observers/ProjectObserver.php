<?php

namespace App\Observers;

use App\Enums\Project\ProjectStatus;
use App\Events\Project\ProjectStatusChanged;
use App\Models\Project;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Log;

class ProjectObserver
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
        //
    }

    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        Log::info("Project created: {$project->id} - {$project->name}", [
            'project_id' => $project->id,
            'created_by' => $project->created_by,
        ]);
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        // Track status changes
        if ($project->wasChanged('status')) {
            $this->handleStatusChange($project);
        }
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $deletedBy = $this->authService->user();

        Log::info("Project deleted: {$project->id} - {$project->name}", [
            'project_id' => $project->id,
            'deleted_by' => $deletedBy?->id,
        ]);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        $restoredBy = $this->authService->user();

        Log::info("Project restored: {$project->id} - {$project->name}", [
            'project_id' => $project->id,
            'restored_by' => $restoredBy?->id,
        ]);
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        $forceDeletedBy = $this->authService->user();

        Log::warning("Project force deleted: {$project->id} - {$project->name}", [
            'project_id' => $project->id,
            'force_deleted_by' => $forceDeletedBy?->id,
        ]);
    }

    /**
     * Handle status changes for the project.
     */
    protected function handleStatusChange(Project $project): void
    {
        $oldStatus = ProjectStatus::from($project->getOriginal('status')->value);
        $newStatus = $project->status;

        // Skip if status hasn't actually changed
        if ($oldStatus === $newStatus) {
            return;
        }

        // Get the user who made the change
        $changedBy = $this->authService->user();

        if (! $changedBy) {
            Log::warning("Could not determine who changed project status for project {$project->id}");

            return;
        }

        // Fire the event
        ProjectStatusChanged::dispatch($project, $oldStatus, $newStatus, $changedBy);

        Log::info("Project status changed: {$project->id}", [
            'project_id' => $project->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'changed_by' => $changedBy->id,
        ]);
    }
}
