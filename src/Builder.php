<?php

namespace Alexwijn\CTE;

use Alexwijn\CTE\Query\Builder as QueryBuilder;
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
 * @method $this constraint($constraint = null, $operator = null, $value = null, $boolean = 'and')
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
     * @var \Alexwijn\CTE\Query\Builder
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
        'offset',
        'where',
        'whereNull',
        'whereNotNull'
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
     * @param \Alexwijn\CTE\Query\Builder $query
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
    public function leftJoin($model, $first, $operator = null, $second = null)
    {
        return $this->join($model, $first, $operator, $second, 'left');
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
    public function rightJoin($model, $first, $operator = null, $second = null)
    {
        return $this->join($model, $first, $operator, $second, 'right');
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

        if (in_array($method, $this->passthru)) {
            $this->query->{$method}(...$parameters);
            return $this;
        }

        if ($method === 'constraint') {
            $this->model->constraint(...$parameters);
            return $this;
        }

        return $this->{$method}(...$parameters);
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
