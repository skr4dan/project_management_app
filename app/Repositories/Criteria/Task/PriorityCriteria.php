<?php

namespace App\Repositories\Criteria\Task;

use App\Enums\Task\TaskPriority;
use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;

/**
 * Priority Criteria for Task filtering
 */
class PriorityCriteria implements TaskCriteriaInterface
{
    public function __construct(
        private TaskPriority $priority
    ) {}

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Task>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Task>
     */
    public function apply($query)
    {
        return $query->where('priority', $this->priority->value);
    }
}
