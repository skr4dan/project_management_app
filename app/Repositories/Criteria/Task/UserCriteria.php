<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\Task\Contracts\TaskCriteriaInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * User Criteria for Task filtering
 */
class UserCriteria implements TaskCriteriaInterface
{
    public function __construct(
        private int $userId
    ) {}

    /**
     * @param  Builder<\App\Models\Task>  $query
     * @return Builder<\App\Models\Task>
     */
    public function apply($query): Builder
    {
        return $query->where(function ($q) {
            $q->where('created_by', $this->userId)
                ->orWhere('assigned_to', $this->userId);
        });
    }
}
