<?php

namespace App\DTOs\Task;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;

/**
 * Task Data Transfer Object
 *
 * Represents task data as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
readonly class TaskDTO
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public ?int $project_id,
        public ?int $assigned_to,
        public ?int $created_by,
        public ?\DateTime $due_date,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            status: isset($data['status'])
                ? ($data['status'] instanceof TaskStatus ? $data['status'] : TaskStatus::from($data['status']))
                : TaskStatus::Pending,
            priority: isset($data['priority'])
                ? ($data['priority'] instanceof TaskPriority ? $data['priority'] : TaskPriority::from($data['priority']))
                : TaskPriority::Medium,
            project_id: $data['project_id'] ?? null,
            assigned_to: $data['assigned_to'] ?? null,
            created_by: $data['created_by'] ?? null,
            due_date: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            created_at: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updated_at: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null,
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'project_id' => $this->project_id,
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get fillable data for model creation/update
     *
     * @return array<string, mixed>
     */
    public function toModelArray(): array
    {
        $data = $this->toArray();

        // Remove readonly fields
        unset($data['id'], $data['created_at'], $data['updated_at']);

        return $data;
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date < new \DateTime && $this->status !== TaskStatus::Completed;
    }

    /**
     * Check if task is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::Completed;
    }

    /**
     * Check if task is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === TaskStatus::InProgress;
    }

    /**
     * Check if task is pending
     */
    public function isPending(): bool
    {
        return $this->status === TaskStatus::Pending;
    }

    /**
     * Check if task has high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === TaskPriority::High;
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty(trim($this->title))) {
            throw new \InvalidArgumentException('Task title cannot be empty');
        }

        if ($this->due_date && $this->due_date < new \DateTime) {
            throw new \InvalidArgumentException('Due date cannot be in the past');
        }

        if ($this->project_id === null) {
            throw new \InvalidArgumentException('Task must belong to a project');
        }

        if ($this->created_by === null) {
            throw new \InvalidArgumentException('Task must have a creator');
        }
    }

    /**
     * Create a new instance with modified data
     *
     * @param  array<string, mixed>  $changes
     */
    public function with(array $changes): self
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'project_id' => $this->project_id,
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return new self(...array_merge($data, $changes));
    }

    /**
     * Get days until due date
     */
    public function getDaysUntilDue(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        $now = new \DateTime;
        $interval = $now->diff($this->due_date);

        return $interval->days * ($interval->invert ? -1 : 1);
    }

    /**
     * Check if task is urgent (high priority and due soon)
     */
    public function isUrgent(): bool
    {
        $daysUntilDue = $this->getDaysUntilDue();

        return $this->priority === TaskPriority::High &&
               $daysUntilDue !== null &&
               $daysUntilDue <= 3 &&
               $this->status !== TaskStatus::Completed;
    }

    /**
     * Check if task can be started
     */
    public function canBeStarted(): bool
    {
        return $this->status === TaskStatus::Pending &&
               $this->assigned_to !== null;
    }

    /**
     * Check if task can be completed
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, [TaskStatus::Pending, TaskStatus::InProgress]);
    }

    /**
     * Check if task can be reassigned
     */
    public function canBeReassigned(): bool
    {
        return $this->status !== TaskStatus::Completed;
    }

    /**
     * Get task age in days
     */
    public function getAgeInDays(): int
    {
        if (! $this->created_at) {
            return 0;
        }

        $now = new \DateTime;
        $interval = $this->created_at->diff($now);

        return $interval->days;
    }

    /**
     * Check if task is assigned
     */
    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    /**
     * Get task priority level (1-3, where 3 is highest)
     */
    public function getPriorityLevel(): int
    {
        return match ($this->priority) {
            TaskPriority::Low => 1,
            TaskPriority::Medium => 2,
            TaskPriority::High => 3,
        };
    }
}
