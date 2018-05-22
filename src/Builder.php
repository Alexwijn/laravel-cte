<?php

namespace Alexwijn\CTE;

use Alexwijn\CTE\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

/**
 * Alexwijn\CTE\Builder
 *
 * @method $this select($columns = ['*'])
 * @method $this addSelect($column)
 * @method $this groupBy($column)
 * @method $this take($value)
 * @method $this limit($value)
 * @method $this offset($value)
 * @method $this orderBy($column, $direction)
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
 *
 * @method $this where($column, $operator = null, $value = null, $boolean = 'and')
 * @method $this orWhere($column, $operator = null, $value = null)
 * @method $this whereRaw($sql, array $bindings = [], $boolean = 'and')
 * @method $this orWhereRaw($sql, array $bindings = [])
 * @method $this whereBetween($column, array $values, $boolean = 'and', $not = false)
 * @method $this orWhereBetween($column, array $values)
 * @method $this whereNotBetween($column, array $values, $boolean = 'and')
 * @method $this orWhereNotBetween($column, array $values)
 * @method $this whereNested(\Closure $callback, $boolean = 'and')
 * @method $this addNestedWhereQuery($query, $boolean = 'and')
 * @method $this whereSub($column, $operator, \Closure $callback, $boolean)
 * @method $this whereExists(\Closure $callback, $boolean = 'and', $not = false)
 * @method $this orWhereExists(\Closure $callback, $not = false)
 * @method $this whereNotExists(\Closure $callback, $boolean = 'and')
 * @method $this whereExistsQuery(Builder $query, $boolean = 'and', $not = false)
 * @method $this orWhereNotExists(\Closure $callback)
 * @method $this whereIn($column, $values, $boolean = 'and', $not = false)
 * @method $this orWhereIn($column, $values)
 * @method $this whereNotIn($column, $values, $boolean = 'and')
 * @method $this orWhereNotIn($column, $values)
 * @method $this whereInSub($column, \Closure $callback, $boolean, $not)
 * @method $this whereNull($column, $boolean = 'and', $not = false)
 * @method $this orWhereNull($column)
 * @method $this whereNotNull($column, $boolean = 'and')
 * @method $this orWhereNotNull($column)
 * @method $this whereDate($column, $operator, $value, $boolean = 'and')
 * @method $this whereDay($column, $operator, $value, $boolean = 'and')
 * @method $this whereMonth($column, $operator, $value, $boolean = 'and')
 * @method $this whereYear($column, $operator, $value, $boolean = 'and')
 *
 * @method $this having($column, $operator = null, $value = null, $boolean = 'and')
 * @method $this orHaving($column, $operator = null, $value = null)
 * @method $this havingRaw($sql, array $bindings = [], $boolean = 'and')
 * @method $this orHavingRaw($sql, array $bindings = [])
 *
 * @method $this dynamicWhere($method, $parameters)
 */
class Builder
{
    /**
     * The model being queried.
     *
     * @var Model
     */
    protected $model;

    /**
     * The base query builder instance.
     *
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @var array
     */
    protected $passthru = [
        'addSelect',
        'select',
        'orderBy',
        'groupBy',
        'take',
        'limit',
        'offset'
    ];

    /**
     * Applied global scopes.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Builder constructor.
     *
     * @param QueryBuilder $query
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Register a new global scope.
     *
     * @param  string                       $identifier
     * @param  \Alexwijn\CTE\Scope|\Closure $scope
     * @return $this
     */
    public function withGlobalScope($identifier, $scope)
    {
        $this->scopes[$identifier] = $scope;

        if (method_exists($scope, 'extend')) {
            $scope->extend($this);
        }

        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param string|\Alexwijn\CTE\Model $table
     * @param string                     $one
     * @param string|string              $operator
     * @param string|null                $two
     * @param string                     $type
     * @param bool                       $where
     * @return $this
     */
    public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        if (is_string($table) && class_exists($table)) {
            $table = new $table;
        }

        if ($table instanceof Model) {
            $this->query->with($table);
            $table = $table->getAlias();
        }

        $this->query->join($table, $one, $operator, $two, $type, $where);

        return $this;
    }

    /**
     * Add a left join to the query.
     *
     * @param string|\Alexwijn\CTE\Model $table
     * @param string                     $first
     * @param null                       $operator
     * @param null                       $second
     * @return \Alexwijn\CTE\Builder
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
     *
     * @param string|\Alexwijn\CTE\Model $table
     * @param string                     $first
     * @param null                       $operator
     * @param null                       $second
     * @return \Alexwijn\CTE\Builder
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array $columns
     * @return Fluent
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Pluck a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public function pluck($column)
    {
        if ($result = $this->first([$column])) {
            return $result->get($column);
        };

        return null;
    }

    public function chunk($count, \Closure $callback)
    {
        $this->query->chunk($count, function ($rows) use ($callback) {
            $callback(Collection::make($rows)->map(function ($row) {
                return new Fluent($row);
            }));
        });
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return $this->query->count($columns);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->query->min($column);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->query->max($column);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->query->sum($column);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->query->avg($column);
    }

    /**
     * Execute the query.
     *
     * @param  array $columns
     * @return array|Collection
     */
    public function get($columns = ['*'])
    {
        return Collection::make($this->query->get($columns))->map(function ($row) {
            return new Fluent($row);
        });
    }

    /**
     * Call the given local model scopes.
     *
     * @param  array $scopes
     * @return mixed
     */
    public function scopes(array $scopes)
    {
        $builder = $this;

        foreach ($scopes as $scope => $parameters) {
            // If the scope key is an integer, then the scope was passed as the value and
            // the parameter list is empty, so we will format the scope name and these
            // parameters here. Then, we'll be ready to call the scope on the model.
            if (is_int($scope)) {
                list($scope, $parameters) = [$parameters, []];
            }

            // Next we'll pass the scope callback to the callScope method which will take
            // care of grouping the "wheres" properly so the logical order doesn't get
            // messed up when adding scopes. Then we'll return back out the builder.
            $builder = $builder->callScope(
                [$this->model, 'scope' . ucfirst($scope)],
                (array)$parameters
            );
        }

        return $builder;
    }

    /**
     * Apply the scopes to the Eloquent builder instance and return it.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function applyScopes()
    {
        if (!$this->scopes) {
            return $this;
        }

        $builder = clone $this;

        foreach ($this->scopes as $identifier => $scope) {
            if (!isset($builder->scopes[$identifier])) {
                continue;
            }

            $builder->callScope(function (Builder $builder) use ($scope) {
                // If the scope is a Closure we will just go ahead and call the scope with the
                // builder instance. The "callScope" method will properly group the clauses
                // that are added to this query so "where" clauses maintain proper logic.
                if ($scope instanceof \Closure) {
                    $scope($builder);
                }

                // If the scope is a scope object, we will call the apply method on this scope
                // passing in the builder and the model instance. After we run all of these
                // scopes we will return back the builder instance to the outside caller.
                if ($scope instanceof Scope) {
                    $scope->apply($builder, $this->getModel());
                }
            });
        }

        return $builder;
    }

    /**
     * @return string
     */
    public function toSql()
    {
        return $this->query->toSql();
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->model, $scope = 'scope' . ucfirst($method))) {
            return $this->callScope([$this->model, $scope], $parameters);
        }

        if (in_array($method, $this->passthru) ||
            str_contains($method, 'where') ||
            str_contains($method, 'having')
        ) {
            $this->query->{$method}(...$parameters);
            return $this;
        }

        if ($method !== 'constraint' && str_contains($method, 'constraint')) {
            return $this->model->constraint(function (IlluminateBuilder $builder) use ($method, $parameters) {
                $method = str_replace('constraint', 'where', $method);
                $builder->$method(...$parameters);
            });
        }

        if ($method === 'constraint') {
            $this->model->constraint(...$parameters);
            return $this;
        }

        return trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
    }

    /**
     * @param \Alexwijn\CTE\Model $model
     * @return $this
     */
    public function with(Model $model)
    {
        $this->query->with($model);

        return $this;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Alexwijn\CTE\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param \Alexwijn\CTE\Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Apply the given scope on the current builder instance.
     *
     * @param  callable $scope
     * @param  array    $parameters
     * @return mixed
     */
    protected function callScope(callable $scope, $parameters = [])
    {
        array_unshift($parameters, $this);

        $result = $scope(...array_values($parameters)) ?? $this;

        return $result;
    }
}
