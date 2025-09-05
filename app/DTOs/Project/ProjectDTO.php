<?php

namespace App\DTOs\Project;

use App\Enums\Project\ProjectStatus;

/**
 * Project Data Transfer Object
 *
 * Represents project data as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
readonly class ProjectDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $description,
        public ProjectStatus $status,
        public ?int $created_by,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            status: isset($data['status']) ? ProjectStatus::from($data['status']) : ProjectStatus::Active,
            created_by: $data['created_by'] ?? null,
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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'created_by' => $this->created_by,
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
     * Check if project is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === ProjectStatus::Active;
    }

    /**
     * Check if project is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === ProjectStatus::Completed;
    }

    /**
     * Check if project is archived
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->status === ProjectStatus::Archived;
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validate(): void
    {
        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Project name cannot be empty');
        }

        if (strlen($this->name) < 3) {
            throw new \InvalidArgumentException('Project name must be at least 3 characters long');
        }

        if ($this->created_by === null) {
            throw new \InvalidArgumentException('Project must have a creator');
        }
    }

    /**
     * Create a new instance with modified data
     *
     * @param array<string, mixed> $changes
     * @return self
     */
    public function with(array $changes): self
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return new self(...array_merge($data, $changes));
    }

    /**
     * Get project status display name
     *
     * @return string
     */
    public function getStatusDisplayName(): string
    {
        return match ($this->status) {
            ProjectStatus::Active => 'Active',
            ProjectStatus::Completed => 'Completed',
            ProjectStatus::Archived => 'Archived',
        };
    }

    /**
     * Get project slug for URLs
     *
     * @return string
     */
    public function getSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->name);
    }

    /**
     * Check if project can be edited
     *
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return $this->status !== ProjectStatus::Archived;
    }

    /**
     * Check if project can have new tasks added
     *
     * @return bool
     */
    public function canAddTasks(): bool
    {
        return in_array($this->status, [ProjectStatus::Active]);
    }

    /**
     * Check if project can be deleted
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return $this->status === ProjectStatus::Active;
    }

    /**
     * Get project age in days
     *
     * @return int
     */
    public function getAgeInDays(): int
    {
        if (!$this->created_at) {
            return 0;
        }

        $now = new \DateTime();
        $interval = $this->created_at->diff($now);

        return $interval->days;
    }

    /**
     * Get short description (first 100 characters)
     *
     * @return string
     */
    public function getShortDescription(): string
    {
        if (!$this->description) {
            return '';
        }

        return strlen($this->description) > 100
            ? substr($this->description, 0, 100) . '...'
            : $this->description;
    }
}
