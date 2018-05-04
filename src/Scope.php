<?php

namespace Alexwijn\CTE;

/**
 * Alexwijn\CTE\Scope
 */
interface Scope
{
    /**
     * Apply the scope to a given query builder.
     *
     * @param  \Alexwijn\CTE\Builder $builder
     * @param  \Alexwijn\CTE\Model   $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
