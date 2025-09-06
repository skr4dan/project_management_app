<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;

/**
 * Assignee Criteria for Task filtering
 */
class AssigneeCriteria implements TaskCriteriaInterface
{
    public function __construct(
        private int $userId
    ) {}

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Task>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Task>
     */
    public function apply($query)
    {
        return $query->where('assigned_to', $this->userId);
    }
}
