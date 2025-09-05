<?php

namespace App\Repositories\Criteria\Task;

use App\Repositories\Criteria\CriteriaInterface;

/**
 * User Criteria for Task filtering
 */
class UserCriteria implements CriteriaInterface
{
    public function __construct(
        private int $userId
    ) {}

    public function apply($query)
    {
        return $query->where(function ($q) {
            $q->where('created_by', $this->userId)
              ->orWhere('assigned_to', $this->userId);
        });
    }
}
