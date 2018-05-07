<?php

namespace Alexwijn\CTE;

use Alexwijn\CTE\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Support\Arr;

/**
 * Alexwijn\CTE\Model
 *
 * @mixin \Alexwijn\CTE\Builder
 */
abstract class Model
{
    /**
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of global scopes on the model.
     *
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * The table alias associated with the model.
     *
     * @var string
     */
    protected $alias;

    /**
     * @var \Closure[]
     */
    protected $constraints = [];

    /**
     * Model constructor.
     */
    public function __construct()
    {
        $this->bootIfNotBooted();
        $this->constructTraits();
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Register a new global scope on the model.
     *
     * @param  \Alexwijn\CTE\Scope|\Closure|string $scope
     * @param  \Closure|null                       $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function addGlobalScope($scope, \Closure $implementation = null)
    {
        if (is_string($scope) && !is_null($implementation)) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        } elseif ($scope instanceof \Closure) {
            return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
        } elseif ($scope instanceof Scope) {
            return static::$globalScopes[static::class][get_class($scope)] = $scope;
        }

        throw new \InvalidArgumentException('Global scope must be an instance of Closure or Scope.');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        foreach (class_uses_recursive(get_called_class()) as $trait) {
            if (method_exists(get_called_class(), $method = 'boot' . class_basename($trait))) {
                forward_static_call([get_called_class(), $method]);
            }
        }
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Alexwijn\CTE\Builder
     */
    public function newQuery($includeSelf = true)
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes($includeSelf));
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return \Alexwijn\CTE\Builder
     */
    public function newQueryWithoutScopes($includeSelf = true)
    {
        $builder = $this->newBuilder();

        // When we want to include our self in the
        // WITH statement we make sure that it is added.
        if ($includeSelf) {
            $builder->with($this);
        }

        // Once we have the builder, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this);
    }

    /**
     * Register the global scopes for this builder instance.
     *
     * @param  \Alexwijn\CTE\Builder $builder
     * @return \Alexwijn\CTE\Builder
     */
    public function registerGlobalScopes($builder)
    {
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Create a new builder for the model.
     *
     * @return \Alexwijn\CTE\Builder
     */
    public function newBuilder()
    {
        return new Builder($this->newBaseQueryBuilder());
    }

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return app('db.connection');
    }

    /**
     * When we create the base query we add the following constraint to the builder.
     *
     * @param array|string|null $constraints
     * @return $this
     */
    public function constraint($constraint = null, $operator = null, $value = null, $boolean = 'and')
    {
        if (!is_array($constraint) && !($constraint instanceof \Closure)) {
            $constraint = function (IlluminateBuilder $query) use ($constraint, $operator, $value, $boolean) {
                if (is_array($value)) {
                    return $query->whereIn($constraint, $value, $boolean);
                }

                if (is_array($operator)) {
                    return $query->whereIn($constraint, $operator, $boolean);
                }

                return $query->where($constraint, $operator, $value, $boolean);
            };
        }

        $this->constraints = array_merge($this->constraints, (array)$constraint);

        return $this;
    }

    public function constraints()
    {
        return $this->constraints;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Get the global scopes for this class instance.
     *
     * @return array
     */
    public function getGlobalScopes()
    {
        return Arr::get(static::$globalScopes, static::class, []);
    }

    /**
     * Creates a new Illuminate Query Builder with the appropriate columns and criteria.
     *
     * @return \Illuminate\Database\Query\Builder|string
     */
    abstract public function createWithQuery();

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->newQuery(), $method], $parameters);
    }

    /**
     * Construct all of the constructables traits on the form request.
     *
     * @return void
     */
    protected function constructTraits()
    {
        foreach (class_uses_recursive(get_called_class()) as $trait) {
            if (method_exists($this, $method = 'construct' . class_basename($trait))) {
                call_user_func([$this, $method]);
            }
        }
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        $class = get_class($this);

        if (!isset(static::$booted[$class])) {
            static::$booted[$class] = true;

            static::boot();
        }
    }

    /**
     * Get a new query builder instance for the connection.
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = new MariadbGrammar();

        $builder = new QueryBuilder($conn, $grammar, $conn->getPostProcessor());

        return $builder->from($this->getAlias());
    }
}
