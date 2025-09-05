<?php

namespace App\DTOs\Role;

/**
 * Role Data Transfer Object
 *
 * Represents role data as a value object for transfer between layers.
 * Immutable and validated to ensure data integrity.
 */
readonly class RoleDTO
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public ?int $id,
        public string $slug,
        public string $name,
        public array $permissions,
        public bool $is_active,
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
            slug: $data['slug'] ?? '',
            name: $data['name'] ?? '',
            permissions: $data['permissions'] ?? [],
            is_active: $data['is_active'] ?? true,
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
            'slug' => $this->slug,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'is_active' => $this->is_active,
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
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if role has any of the given permissions
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return ! empty(array_intersect($permissions, $this->permissions));
    }

    /**
     * Check if role has all of the given permissions
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->permissions));
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty(trim($this->slug))) {
            throw new \InvalidArgumentException('Role slug cannot be empty');
        }

        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $this->slug)) {
            throw new \InvalidArgumentException('Role slug must contain only lowercase letters, numbers, hyphens, and underscores, and start with a letter');
        }

        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }

        // Validate permissions format
        foreach ($this->permissions as $permission) {
            if (! is_string($permission) || empty(trim($permission))) {
                throw new \InvalidArgumentException('All permissions must be non-empty strings');
            }

            if (! preg_match('/^[a-z][a-z0-9._]*$/', $permission)) {
                throw new \InvalidArgumentException('Permission format is invalid: '.$permission);
            }
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
            'slug' => $this->slug,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return new self(...array_merge($data, $changes));
    }

    /**
     * Check if role is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this->slug === 'admin';
    }

    /**
     * Check if role is manager
     */
    public function isManager(): bool
    {
        return $this->slug === 'manager';
    }

    /**
     * Check if role is user
     */
    public function isUser(): bool
    {
        return $this->slug === 'user';
    }

    /**
     * Get permission count
     */
    public function getPermissionCount(): int
    {
        return count($this->permissions);
    }

    /**
     * Get permissions grouped by resource
     *
     * @return array<string, array<int, string>>
     */
    public function getPermissionsByResource(): array
    {
        $grouped = [];

        foreach ($this->permissions as $permission) {
            [$resource, $action] = explode('.', $permission, 2) + [null, null];

            if ($resource && $action) {
                $grouped[$resource][] = $action;
            }
        }

        return $grouped;
    }

    /**
     * Check if role has permissions for a specific resource
     *
     * @return array<int, string>
     */
    public function getResourcePermissions(string $resource): array
    {
        return $this->getPermissionsByResource()[$resource] ?? [];
    }
}
