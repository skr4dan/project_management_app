<?php

namespace App\Repositories\Criteria\Task\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Criteria Interface
 *
 * Defines the contract for criteria objects used in repository filtering.
 */
interface TaskCriteriaInterface
{
    /**
     * Apply the criteria to the query builder
     *
     * @param  Builder<\App\Models\Task>  $query
     * @return Builder<\App\Models\Task>
     */
    public function apply($query);
}
