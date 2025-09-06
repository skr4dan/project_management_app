<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;

/**
 * Project Criteria for Task filtering
 */
class ProjectCriteria implements TaskCriteriaInterface
{
    public function __construct(
        private int $projectId
    ) {}

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Task>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Task>
     */
    public function apply($query)
    {
        return $query->where('project_id', $this->projectId);
    }
}
