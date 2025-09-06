<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sort Criteria for Task ordering
 */
class SortCriteria implements TaskCriteriaInterface
{
    /**
     * @var array<string>
     */
    private array $allowedSortFields = [
        'due_date', 'created_at',
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
        if (! in_array($this->field, $this->allowedSortFields)) {
            throw new \InvalidArgumentException("Invalid sort field: {$this->field}");
        }
    }

    private function validateDirection(): void
    {
        $this->direction = strtolower($this->direction);
        if (! in_array($this->direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Invalid sort direction: {$this->direction}");
        }
    }

    /**
     * @param  Builder<\App\Models\Task>  $query
     * @return Builder<\App\Models\Task>
     */
    public function apply($query): Builder
    {
        return $query->orderBy($this->field, $this->direction);
    }
}
