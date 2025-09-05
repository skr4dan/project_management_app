<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\CriteriaInterface;

/**
 * Assignee Criteria for Task filtering
 */
class AssigneeCriteria implements CriteriaInterface
{
    public function __construct(
        private int $userId
    ) {}

    public function apply($query)
    {
        return $query->where('assigned_to', $this->userId);
    }
}
