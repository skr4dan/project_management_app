<?php

namespace App\Repositories\Criteria;

/**
 * Criteria Interface
 *
 * Defines the contract for criteria objects used in repository filtering.
 */
interface CriteriaInterface
{
    /**
     * Apply the criteria to the query builder
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply($query);
}
