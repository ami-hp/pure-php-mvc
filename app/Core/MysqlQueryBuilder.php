<?php

namespace App\Core;

use App\Core\Mysql;
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
    protected false|int $limit = false;
    protected false|int $offset = false;
    protected bool $distinct = false;
    protected bool $toSqlStatus = false;

    protected string $action = "select";


    private array $columns = [];
    private array $values = [];

    private array $update = [];
    private bool $ignore = false;

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
                    $this->buildLimit(),
                    $this->buildOffset()
                );
                break;
            case 'insert':
                $sql = array_merge(
                    [$this->buildInsertBase()],
                    [$this->buildValues()],
                    [$this->buildOnDuplicateKeyUpdate()]
                );
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

    public function get()
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

        $sql = $this->toSql();
        vamp($sql);
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

    public function insert()
    {
        $sql = $this->setAction('insert')->buildSql();
        $stmt = $this->connection->prepare($sql);
        // Binding values
        foreach ($this->values as $i => $valueSet) {
            foreach ($valueSet as $j => $value) {
                $stmt->bindValue(":val_{$i}_{$j}", $value);
            }
        }
        // Binding update values if present
        foreach ($this->update as $column => $value) {
            $stmt->bindValue(":update_$column", $value);
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
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function values(...$valuesSets): static
    {
        foreach ($valuesSets as $values) {
            $this->values[] = $values;
        }
        return $this;
    }

    public function insertIgnore(): static
    {
        $this->ignore = true;
        return $this;
    }

    private function buildInsertBase()
    {
        return $this->ignore
            ? "INSERT IGNORE INTO {$this->table} (".implode(', ', $this->columns).") "
            : "INSERT INTO {$this->table} (".implode(', ', $this->columns).") ";
    }

    private function buildValues()
    {
        $valuesParts = [];
        $placeholders = [];
        foreach ($this->values as $i => $valueSet) {
            $rowPlaceholders = [];
            foreach ($valueSet as $j => $value) {
                $placeholder = ":val_{$i}_{$j}";
                $rowPlaceholders[] = $placeholder;
                $placeholders[$placeholder] = $value;
            }
            $valuesParts[] = "(".implode(', ', $rowPlaceholders).")";
        }
        return "VALUES ".implode(', ', $valuesParts);
    }

    private function buildOnDuplicateKeyUpdate()
    {
        if (!empty($this->update)) {
            $updateParts = [];
            foreach ($this->update as $column => $value) {
                $updateParts[] = "$column = :update_$column";
            }
            return " ON DUPLICATE KEY UPDATE ".implode(', ', $updateParts);
        }
        return "";
    }

    private function quoteValue($value)
    {
        // This is a simple example, in real applications you should use prepared statements
        return is_numeric($value) ? $value : "'".addslashes($value)."'";
    }

    /**
     * example: onDuplicateKeyUpdate(['email' => 'eve_new@example.com'])
     * @param  array  $update
     * @return $this
     */
    public
    function onDuplicateKeyUpdate(
        array $update
    ): static {
        $this->update = $update;
        return $this;
    }


    /**
     * PRIVATE METHODS
     * ============
     * only used in this class
     */

    protected
    function setAction(
        string $action
    ): static {
        $this->action = $action;
        return $this;
    }

    private
    function buildSelect(): array
    {
        $sql = ["SELECT"];
        if ($this->distinct) {
            $sql[] = "DISTINCT";
        }
        $sql[] = empty($this->selects) ? "*" : implode(', ', $this->selects);
        $sql[] = "FROM {$this->table}";
        return $sql;
    }

    private
    function buildWhere(): array
    {
        if ($this->wheres) {
            return ["WHERE ".implode(' AND ', $this->wheres)];
        }
        return [];
    }

    private
    function buildGroupBy(): array
    {
        if ($this->groupBys) {
            return ["GROUP BY ".implode(', ', $this->groupBys)];
        }
        return [];
    }

    private
    function buildLimit(): array
    {
        if ($this->limit) {
            return ["LIMIT {$this->limit}"];
        }
        return [];
    }

    private
    function buildOffset(): array
    {
        if ($this->offset) {
            return ["OFFSET {$this->offset}"];
        }
        return [];
    }
}
