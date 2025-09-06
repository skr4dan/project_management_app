<?php

namespace App\Repositories\Criteria\Task;

use App\Enums\Task\TaskStatus;
use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status Criteria for Task filtering
 */
class StatusCriteria implements TaskCriteriaInterface
{
    public function __construct(
        private TaskStatus $status
    ) {}

    /**
     * @param  Builder<\App\Models\Task>  $query
     * @return Builder<\App\Models\Task>
     */
    public function apply($query): Builder
    {
        return $query->where('status', $this->status->value);
    }
}
