<?php

namespace App\DTOs;

/**
 * Pagination Data Transfer Object
 *
 * Represents pagination parameters for API requests.
 * Immutable and validated to ensure data integrity.
 */
readonly class PaginationDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
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
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 15,
        );
    }

    /**
     * Create DTO from request data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->perPage < 1) {
            throw new \InvalidArgumentException('perPage must be at least 1');
        }

        if ($this->page < 1) {
            throw new \InvalidArgumentException('page must be at least 1');
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
            'page' => $this->page,
            'perPage' => $this->perPage,
        ];

        return new self(...array_merge($data, $changes));
    }
}
