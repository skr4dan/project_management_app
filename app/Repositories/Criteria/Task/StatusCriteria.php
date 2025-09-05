<?php

namespace App\Repositories\Criteria\Task;

use App\Enums\Task\TaskStatus;
use App\Repositories\Criteria\CriteriaInterface;

/**
 * Status Criteria for Task filtering
 */
class StatusCriteria implements CriteriaInterface
{
    public function __construct(
        private TaskStatus $status
    ) {}

    public function apply($query)
    {
        return $query->where('status', $this->status->value);
    }
}
