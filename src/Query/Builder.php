<?php

namespace Alexwijn\CTE\Query;

use Alexwijn\CTE\Model;

/**
 * Alexwijn\CTE\Query\Builder
 */
class Builder extends \Illuminate\Database\Query\Builder
{
    /**
     * @var Model[]
     */
    public $with;

    public function with(Model $model)
    {
        $this->with[] = $model;

        return $this;
    }
}
