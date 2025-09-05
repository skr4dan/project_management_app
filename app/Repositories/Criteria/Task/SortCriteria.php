<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\CriteriaInterface;

/**
 * Sort Criteria for Task ordering
 */
class SortCriteria implements CriteriaInterface
{
    private array $allowedSortFields = [
        'due_date', 'created_at'
    ];

    public function __construct(
        private string $field,
        private string $direction = 'asc'
    ) {
        $this->validateField();
        $this->validateDirection();
    }

    private function validateField(): void
    {
        if (!in_array($this->field, $this->allowedSortFields)) {
            throw new \InvalidArgumentException("Invalid sort field: {$this->field}");
        }
    }

    private function validateDirection(): void
    {
        $this->direction = strtolower($this->direction);
        if (!in_array($this->direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Invalid sort direction: {$this->direction}");
        }
    }

    public function apply($query)
    {
        return $query->orderBy($this->field, $this->direction);
    }
}
