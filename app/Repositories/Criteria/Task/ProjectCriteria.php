<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\CriteriaInterface;

/**
 * Project Criteria for Task filtering
 */
class ProjectCriteria implements CriteriaInterface
{
    public function __construct(
        private int $projectId
    ) {}

    public function apply($query)
    {
        return $query->where('project_id', $this->projectId);
    }
}
