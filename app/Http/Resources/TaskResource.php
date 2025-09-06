<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Task */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'project' => $this->whenLoaded(
                'project',
                fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
            ),
            'assigned_to' => $this->whenLoaded(
                'assignedTo',
                fn (User $assignedTo) => [
                    'id' => $assignedTo->id,
                    'first_name' => $assignedTo->first_name,
                    'last_name' => $assignedTo->last_name,
                    'email' => $assignedTo->email,
                ],
            ),
            'created_by' => $this->whenLoaded(
                'createdBy',
                fn (User $createdBy) => [
                    'id' => $createdBy->id,
                    'first_name' => $createdBy->first_name,
                    'last_name' => $createdBy->last_name,
                    'email' => $createdBy->email,
                ],
            ),
            'due_date' => $this->due_date?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
