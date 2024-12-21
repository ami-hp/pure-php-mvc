<?php

namespace App\Core;

use PDO;
use PDOException;

// using Data Mapper, Behavioral Design Pattern

/**
 * SQL Initiators
 * --------
 * Data Manipulation Language (DML) : SELECT, INSERT, UPDATE, DELETE
 * Data Definition Language (DDL) : CREATE, ALTER, DROP, TRUNCATE
 * Data Control Language (DCL) : GRANT, REVOKE
 * Transaction Control Language (TCL) : COMMIT, ROLLBACK, SAVEPOINT, SET TRANSACTION
 * Miscellaneous and Other Commands : EXPLAIN, SHOW, DESCRIBE (DESC), USE, LOCK, UNLOCK, ANALYZE, OPTIMIZE, RENAME
 * Administrative Commands: FLUSH, PURGE, RESET, KILL
 */

/**
 *
 */
class MysqlQueryBuilder
{
    protected PDO $connection;
    protected string $table;
    protected array $selects = [];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $groupBys = [];
    protected array $orderBys = [];
    protected false|int $limit = false;
    protected false|int $offset = false;
    protected bool $distinct = false;
    protected bool $toSqlStatus = false;

    protected string $action = "select";

    protected array $insertArrays = [];

    private array $insertColumns = [];
    private array $insertValues = [];

    private array $insertUpdateOnDuplicate = [];
    private bool $insertIgnore = false;

    public function __construct()
    {
        $this->connection = Mysql::getInstance();
    }

    public function table($table): static
    {
        $this->table = $table;
        return $this;
    }

    public function where($column, $operator, $value): static
    {
        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function toSql(bool $state = true): static
    {
        $this->toSqlStatus = $state;
        return $this;
    }

    public function buildSql(): string
    {
        $sql = [];

        switch ($this->action) {
            case 'select':
                $sql = array_merge(
                    $this->buildSelect(),
                    $this->buildWhere(),
                    $this->buildGroupBy(),
                    $this->buildOrderBy(),
                    $this->buildLimit(),
                    $this->buildOffset()
                );
                break;
            case 'insert':
                $sql = $this->buildInsert();
                break;
            case 'update':
                break;
            case 'delete':
                break;
            default:
                break;
        }
        return implode(' ', $sql);
    }

    public function get(): false|array|string
    {
        $sql = $this->setAction('select')->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first()
    {
        $this->limit(1);
        $sql = $this->setAction('select')->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all()
    {
        $stmt = $this->connection->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table}";
        if ($this->wheres) {
            $sql .= " WHERE ".implode(' AND ', $this->wheres);
        }
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function insert(array ...$dataArrays): bool
    {
        $this->insertArrays = array_merge($this->insertArrays, $dataArrays);
        $sql = $this->setAction('insert')->buildSql();
        $stmt = $this->connection->prepare($sql);

        foreach ($this->insertArrays as $i => $valueSet) {
            foreach ($valueSet as $j => $value) {
                $stmt->bindValue(":val_{$i}_{$j}", $value);
            }

            if (!empty($this->insertUpdateOnDuplicate) && $duplication = $this->insertUpdateOnDuplicate[$i]) {
                foreach ($duplication as $column => $value) {
                    $stmt->bindValue(":update_{$i}_$column", $value);
                }
            }
        }

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error: ".$e->getMessage();
            return false;
        }
    }

    public function update($data)
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "{$key} = ?, ";
            $this->bindings[] = $value;
        }
        $set = rtrim($set, ', ');
        $sql = "UPDATE {$this->table} SET {$set}";
        if ($this->wheres) {
            $sql .= " WHERE ".implode(' AND ', $this->wheres);
        }
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        if ($this->wheres) {
            $sql .= " WHERE ".implode(' AND ', $this->wheres);
        }
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    // Additional methods for other SQL parts like joins, order by, group by, etc., can be added similarly

    /**
     * SELECT Methods
     * ----------
     */

    public function select($columns = ['*']): static
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function groupBy($columns): static
    {
        $this->groupBys = is_array($columns) ? $columns : explode(', ', $columns);
        return $this;
    }

    public function orderBy(array $orders): static
    {
        $this->orderBys = $orders;
        return $this;
    }

    public function limit($limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function unique($column): static
    {
        $this->selects = ["DISTINCT {$column}"];
        return $this;
    }

    /**
     * Insert Methods
     * ----------
     */

    public function insertIgnore(): static
    {
        $this->insertIgnore = true;
        return $this;
    }

    public function onDuplicateKeyUpdate(array|null ...$duplicateOn): static
    {
        $this->insertUpdateOnDuplicate = $duplicateOn;
        return $this;
    }

    private function buildInsertBase(): string
    {
        $sql[] = "INSERT";
        if ($this->insertIgnore) {
            $sql[] = "IGNORE";
        }
        $sql[] = "INTO {$this->table}";

        return implode(' ', $sql);
    }

    private function buildInsertColumns(array $valueSet): string
    {
        return "(".implode(',', array_keys($valueSet)).")";
    }

    private function buildInsertValues($i, $valueSet): string
    {
        $placeholders = [];

        foreach ($valueSet as $j => $value) {
            $placeholder = ":val_{$i}_{$j}";
            $placeholders[$placeholder] = $value;
        }

        return "VALUES (".implode(',', array_keys($placeholders)).")";
    }

    private function buildInsert(): array
    {
        $sql = [];

        foreach ($this->insertArrays as $i => $valueSet) {
            $sql[] = $this->buildInsertBase();

            $sql[] = $this->buildInsertColumns($valueSet);

            $sql[] = $this->buildInsertValues($i, $valueSet);

            if ($duplicate = $this->buildOnDuplicateKeyUpdate($i)) {
                $sql[] = $duplicate;
            }

            $sql[] = ";";
        }

        return $sql;
    }

    private function buildOnDuplicateKeyUpdate($index): ?string
    {
        if (!empty($this->insertUpdateOnDuplicate[$index])) {
            $updateParts = [];
            foreach ($this->insertUpdateOnDuplicate[$index] as $column => $value) {
                $updateParts[] = "$column = :update_{$index}_{$column}";
            }
            return " ON DUPLICATE KEY UPDATE ".implode(', ', $updateParts);
        }
        return null;
    }

    /**
     * PRIVATE METHODS
     * ============
     * only used in this class
     */

    private function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    private function buildSelect(): array
    {
        $sql = ["SELECT"];
        if ($this->distinct) {
            $sql[] = "DISTINCT";
        }
        $sql[] = empty($this->selects) ? "*" : implode(', ', $this->selects);
        $sql[] = "FROM {$this->table}";
        return $sql;
    }

    private function buildWhere(): array
    {
        if ($this->wheres) {
            return ["WHERE ".implode(' AND ', $this->wheres)];
        }
        return [];
    }

    private function buildGroupBy(): array
    {
        if ($this->groupBys) {
            return ["GROUP BY ".implode(', ', $this->groupBys)];
        }
        return [];
    }

    private function buildOrderBy(): array
    {
        $sql = [];
        if ($this->orderBys) {
            foreach ($this->orderBys as $column => $direction) {
                $sql[] = "{$column} ".strtoupper($direction);
            }
            return ["Order BY ".implode(',', $sql)];
        }
        return [];
    }

    private function buildLimit(): array
    {
        if ($this->limit) {
            return ["LIMIT {$this->limit}"];
        }
        return [];
    }

    private function buildOffset(): array
    {
        if ($this->offset) {
            return ["OFFSET {$this->offset}"];
        }
        return [];
    }
}
