<?php

namespace App\Core\Services;

use App\Core\Interfaces\QueryBuilderInterface;
use App\Core\MysqlQueryBuilder;

class DatabaseService
{
    protected QueryBuilderInterface $queryBuilder;

    public function __construct(string $database = 'mysql')
    {
        if ($database === 'mysql') {
            $this->queryBuilder = new MySqlQueryBuilder();
        } elseif ($database === 'postgresql') {
//            $this->queryBuilder = new PostgresqlQueryBuilder();
        }
    }

    public function __call($method, $args)
    {
        return $this->queryBuilder->$method(...$args);
    }
}
