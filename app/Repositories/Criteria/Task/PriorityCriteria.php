<?php

namespace App\Repositories\Criteria\Task;

use App\Enums\Task\TaskPriority;
use App\Repositories\Criteria\CriteriaInterface;

/**
 * Priority Criteria for Task filtering
 */
class PriorityCriteria implements CriteriaInterface
{
    public function __construct(
        private TaskPriority $priority
    ) {}

    public function apply($query)
    {
        return $query->where('priority', $this->priority->value);
    }
}
