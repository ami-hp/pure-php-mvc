<?php

namespace App\Core\Services;

use App\Core\Interfaces\QueryBuilderInterface;
use app\Core\QueryBuilders\MysqlQueryBuilder;

class DatabaseService
{
    protected QueryBuilderInterface $queryBuilder;

    public function __construct(protected ?string $connection = 'mysql')
    {
        $this->setConnection();
    }

    public function connection(string $type): self
    {
        $this->connection = $type;
        $this->setConnection();
        return $this;
    }

    public function __call($method, $args)
    {
        return $this->queryBuilder->$method(...$args);
    }

    private function setConnection(): void
    {
        $this->queryBuilder = match ($this->connection) {
            default => new MySqlQueryBuilder(),
            'postgresql' => new \stdClass() // todo postgresql
        };
    }
}
