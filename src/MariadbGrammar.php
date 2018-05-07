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
            foreach ($statements as $statement) {
                $query = $statement->createWithQuery();
                foreach ($statement->constraints() as $constraint) {
                    $constraint($query);
                }

                $sql = $query->toSql();
                foreach ($query->getBindings() as $binding) {
                    $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
                    $sql = preg_replace('/\?/', $value, $sql, 1);
                }

                $components[] = $statement->getAlias() . ' AS (' . $sql . ')';
            }

            return 'WITH ' . implode($components, ',');
        }

        return '';
    }
}
