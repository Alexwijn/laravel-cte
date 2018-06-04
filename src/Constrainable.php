<?php
/**
 * @copyright Copyright (c) 2018, POS4RESTAURANTS BV. All rights reserved.
 * @internal  Unauthorized copying of this file, via any medium is strictly prohibited.
 */

namespace Alexwijn\CTE;

/**
 * Alexwijn\CTE\Constrainable
 *
 * @method $this constraint($column, $operator = null, $value = null, $boolean = 'and')
 * @method $this orConstraint($column, $operator = null, $value = null)
 * @method $this constraintRaw($sql, array $bindings = [], $boolean = 'and')
 * @method $this orConstraintRaw($sql, array $bindings = [])
 * @method $this constraintBetween($column, array $values, $boolean = 'and', $not = false)
 * @method $this orConstraintBetween($column, array $values)
 * @method $this constraintNotBetween($column, array $values, $boolean = 'and')
 * @method $this orConstraintNotBetween($column, array $values)
 * @method $this constraintNested(\Closure $callback, $boolean = 'and')
 * @method $this addNestedConstraintQuery($query, $boolean = 'and')
 * @method $this constraintSub($column, $operator, \Closure $callback, $boolean)
 * @method $this constraintExists(\Closure $callback, $boolean = 'and', $not = false)
 * @method $this orConstraintExists(\Closure $callback, $not = false)
 * @method $this constraintNotExists(\Closure $callback, $boolean = 'and')
 * @method $this constraintExistsQuery(Builder $query, $boolean = 'and', $not = false)
 * @method $this orConstraintNotExists(\Closure $callback)
 * @method $this constraintIn($column, $values, $boolean = 'and', $not = false)
 * @method $this orConstraintIn($column, $values)
 * @method $this constraintNotIn($column, $values, $boolean = 'and')
 * @method $this orConstraintNotIn($column, $values)
 * @method $this constraintInSub($column, \Closure $callback, $boolean, $not)
 * @method $this constraintNull($column, $boolean = 'and', $not = false)
 * @method $this orConstraintNull($column)
 * @method $this constraintNotNull($column, $boolean = 'and')
 * @method $this orConstraintNotNull($column)
 * @method $this constraintDate($column, $operator, $value, $boolean = 'and')
 * @method $this constraintDay($column, $operator, $value, $boolean = 'and')
 * @method $this constraintMonth($column, $operator, $value, $boolean = 'and')
 * @method $this constraintYear($column, $operator, $value, $boolean = 'and')
 * @method $this dynamicConstraint($method, $parameters)
 */
interface Constrainable
{
    //
}
