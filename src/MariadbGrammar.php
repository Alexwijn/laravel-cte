<?php

namespace Alexwijn\CTE;

use Illuminate\Database\Query\Grammars\MySqlGrammar;

/**
 * Alexwijn\CTE\MariadbGrammar
 */
class MariadbGrammar extends MySqlGrammar
{
    /**
     * @var \Alexwijn\CTE\Builder
     */
    protected $builder;

    /**
     * MariadbGrammar constructor.
     */
    public function __construct()
    {
        array_unshift($this->selectComponents, 'with');
    }

    public function compileWith(Query\Builder $builder)
    {
        if (count($statements = $builder->with) > 0) {
            $components = [];
            $bindings = [];
            foreach ($statements as $statement) {
                $query = $statement->createWithQuery();
                foreach ($statement->constraints() as $constraint) {
                    $constraint($query);
                }

                $bindings = array_merge($query->getRawBindings()['where'], $bindings);
                $components[] = $statement->getAlias() . ' AS (' . $query->toSql() . ')';
            }

            $builder->setBindings(array_merge($bindings, $builder->getRawBindings()['where']));
            return 'WITH ' . implode($components, ',');
        }

        return '';
    }
}
