<?php

namespace App\Repositories\Criteria\Task;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Repositories\Criteria\CriteriaInterface;

/**
 * Task Filter - Builds and applies multiple criteria for task filtering
 */
class TaskFilter
{
    private array $criteria = [];

    public function __construct(array $filters = [])
    {
        $this->buildCriteria($filters);
    }

    /**
     * Add a criteria to the filter
     */
    public function addCriteria(CriteriaInterface $criteria): self
    {
        $this->criteria[] = $criteria;
        return $this;
    }

    /**
     * Apply all criteria to the query
     */
    public function apply($query)
    {
        foreach ($this->criteria as $criteria) {
            $query = $criteria->apply($query);
        }
        return $query;
    }

    /**
     * Build criteria from filter array
     */
    private function buildCriteria(array $filters): void
    {
        // Status filter
        if (isset($filters['status'])) {
            $this->addCriteria(new StatusCriteria(TaskStatus::from($filters['status'])));
        }

        // Priority filter
        if (isset($filters['priority'])) {
            $this->addCriteria(new PriorityCriteria(TaskPriority::from($filters['priority'])));
        }

        // Project filter
        if (isset($filters['project_id'])) {
            $this->addCriteria(new ProjectCriteria((int) $filters['project_id']));
        }

        // Assignee filter
        if (isset($filters['assigned_to'])) {
            $this->addCriteria(new AssigneeCriteria((int) $filters['assigned_to']));
        }

        // Sort criteria
        if (isset($filters['sort_by'])) {
            $direction = $filters['sort_order'] ?? 'asc';
            $this->addCriteria(new SortCriteria($filters['sort_by'], $direction));
        } else {
            // Default sort by created_at desc
            $this->addCriteria(new SortCriteria('created_at', 'desc'));
        }
    }

    /**
     * Get all criteria
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * Check if filter has any criteria
     */
    public function hasCriteria(): bool
    {
        return !empty($this->criteria);
    }
}
