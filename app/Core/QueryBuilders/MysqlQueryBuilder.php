<?php

namespace app\Core\QueryBuilders;

use app\Core\Connections\Mysql;
use App\Core\Interfaces\QueryBuilderInterface;
use PDO;
use PDOException;
use PDOStatement;

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
class MysqlQueryBuilder implements QueryBuilderInterface
{
    protected PDO $connection;
    protected ?string $table = null;
    private array $selects = [];
    private array $andWheres = [];
    private array $orWheres = [];
    private array $bindings = [];
    private array $groupBys = [];
    private array $orderBys = [];
    private false|int $limit = false;
    private false|int $offset = false;
    private bool $distinct = false;
    private bool $toSqlStatus = false;
    private string $action = "select";
    private array $insertArrays = [];
    private array $updateArrays = [];
    private array $insertUpdateOnDuplicate = [];
    private bool $insertIgnore = false;
    private array $join = [];
    const UPDATE_BINDER = ":update_";
    const WHERE_BINDER = ":where_";
    const VALUE_BINDER = ":value_";

    public function __construct()
    {
        $this->connection = Mysql::getInstance();
    }

    public function table($table): static
    {
        $this->table = $table;
        return $this;
    }

    public function where(string|callable $column, string $operator = null, $value = null): static
    {
        $this->processWhere($column, $operator, $value);
        return $this;
    }

    public function whereNot(string|callable $column, string $operator = null, $value = null): static
    {
        $this->processWhere($column, $operator, $value, true);
        return $this;
    }

    private function processWhere(
        string|callable $column,
        string $operator = null,
        $value = null,
        bool $not = false,
        bool $or = false,
        bool $bind = true
    ): void {
        if (is_callable($column)) {
            $query = new static();
            $column($query);

            $sql = "( ".$query->buildWhereClause(false)." )";

            if ($not) {
                $sql = "NOT $sql";
            }

            if (!$or) {
                $this->andWheres[] = $sql;
            } else {
                $this->orWheres[] = $sql;
            }

            $this->bindings = array_merge($this->bindings, $query->bindings);

        } else {

            $sql = $this->handleWhereConditions($column, $operator, $value, $bind);

            if ($not) {
                $sql = "NOT ($sql)";
            }
            if (!$or) {
                $this->andWheres[] = $sql;
            } else {
                $this->orWheres[] = $sql;
            }

        }
    }


    private function handleWhereConditions($column, $operator = null, $value = null, $bind = true) : string
    {
        $sql = [$column];
        if (is_null($operator) && is_null($value)) {
            $this->addNullConditionToWhere($sql);
        } elseif (!is_null($operator) && is_null($value)) {
            $this->addOperatorConditionToWhere($sql, $operator, $column, $bind);
        } elseif (!is_null($operator) && !is_null($value)) {
            $this->addValueConditionToWhere($sql, $operator, $value, $column, $bind);
        } else {
            throw new \Exception("If value is NULL, use null-related methods e.g., whereNull, whereNotNull.");
        }
        return implode(' ', $sql);
    }

    private function addNullConditionToWhere(&$sql): void
    {
        $sql[] = 'IS NULL';
    }

    private function addOperatorConditionToWhere(&$sql, $operator, $column, $bind): void
    {
        if (!$this->getOperator($operator)) {
            $sql[] = '=';
            $value = $operator;
            $placeholder = $this->handleWherePlaceholder($column, $bind, $value);
            $sql[] = $placeholder;
        } else {
            throw new \Exception("If value is NULL, use null-related methods e.g., whereNull, whereNotNull.");
        }
    }

    private function addValueConditionToWhere(&$sql, $operator, $value, $column, $bind): void
    {
        if ($this->getOperator($operator)) {
            $sql[] = $operator;
            $placeholder = $this->handleWherePlaceholder($column, $bind, $value);
            $sql[] = $placeholder;
        } else {
            throw new \Exception("Operator doesn't exist.");
        }
    }

    private function handleWherePlaceholder($column, $bind, &$value): string
    {
        $placeholder = self::WHERE_BINDER.$this->randomPlaceholder()."_$column";
        if ($bind) {
            $this->bindings[$placeholder] = $value;
            return $placeholder;
        } else {
            return $value;
        }
    }

    public function whereColumn(string $column1, string $operator = null, $column2 = null): static
    {
        $this->processWhere($column1, $operator, $column2 , false , false, false);
        return $this;
    }

    public function orWhereColumn(string $column1, string $operator = null, $column2 = null): static
    {
        $this->processWhere($column1, $operator, $column2, false, true , false);
        return $this;
    }

    public function whereAny(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->orWhere($c, $operator, $value);
            }
        });
        return $this;
    }

    public function whereNotAny(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->orWhere($c, $operator, $value);
            }
        }, not: true);
        return $this;
    }

    public function orWhereAny(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->orWhere($c, $operator, $value);
            }
        }, or: true);
        return $this;
    }

    public function orWhereNotAny(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->orWhere($c, $operator, $value);
            }
        }, not: true, or: true);
        return $this;
    }

    public function whereAll(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->where($c, $operator, $value);
            }
        });
        return $this;
    }

    public function whereNone(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->where($c, $operator, $value);
            }
        }, not: true);
        return $this;
    }

    public function orWhereAll(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->where($c, $operator, $value);
            }
        }, or: true);
        return $this;
    }

    public function orWhereNone(array $columns, string $operator = null, mixed $value = null): static
    {
        $this->processWhere(function ($query) use ($columns, $operator, $value) {
            foreach ($columns as $c) {
                $query->where($c, $operator, $value);
            }
        }, not: true, or: true);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->processWhere($column, null, null , false , false , false);
        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $this->processWhere($column, null, null, false, true , false);
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->processWhere($column, null, null, true);
        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $this->processWhere($column, null, null, true, true);
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->processWhereIn($column, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        $this->processWhereIn($column, $values, true);
        return $this;
    }

    private function processWhereIn(string $column, array $values, bool $not = false): void
    {
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = self::WHERE_BINDER.$this->randomPlaceholder()."_{$column}_{$index}";
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $sql = $column.($not ? ' NOT' : '').' IN ('.implode(', ', $placeholders).')';

        $this->andWheres[] = $sql;
    }

    public function whereBetween(string $column, int $value1, int $value2): static
    {

        $this->processWhereBetween($column, $value1, $value2);
        return $this;
    }

    public function whereNotBetween(string $column, int $value1, int $value2): static
    {

        $this->processWhereBetween($column, $value1, $value2, true);
        return $this;
    }

    public function whereBetweenColumn(string $column, string $value1, string $value2): static
    {

        $this->processWhereBetween($column, $value1, $value2 , false , false);
        return $this;
    }

    public function whereNotBetweenColumn(string $column, string $value1, string $value2): static
    {

        $this->processWhereBetween($column, $value1, $value2, true , false);
        return $this;
    }

    private function processWhereBetween(string $column, int|string $value1, int|string $value2, bool $not = false , bool $bind = true): static
    {

        if($bind){
            $placeholder = self::WHERE_BINDER.$this->randomPlaceholder()."_{$column}_1";
            $values[] = $placeholder;
            $this->bindings[$placeholder] = $value1;

            $placeholder = self::WHERE_BINDER.$this->randomPlaceholder()."_{$column}_2";
            $values[] = $placeholder;
            $this->bindings[$placeholder] = $value2;
        } else {
            $values[] = $value1;
            $values[] = $value2;
        }

        $sql = $column.($not ? ' NOT' : '').' BETWEEN '.implode(' AND ', $values);
        $this->andWheres[] = $sql;
        return $this;
    }

    public function orWhere(string|callable $column, string $operator = null, $value = null): static
    {
        $this->processWhere($column, $operator, $value, or: true);
        return $this;
    }

    public function orWhereNot(string|callable $column, string $operator = null, $value = null): static
    {
        $this->processWhere($column, $operator, $value, not: true, or: true);
        return $this;
    }

    public function orWhereExists(string|callable $sql): static
    {
        $this->processWhereExists($sql, or: true);
        return $this;
    }

    public function orWhereNotExists(string|callable $sql): static
    {
        $this->processWhereExists($sql, not: true, or: true);
        return $this;
    }

    public function whereNotExists(string|callable $sql): static
    {
        $this->processWhereExists($sql, not: true);
        return $this;
    }

    public function whereExists(string|callable $sql): static
    {
        $this->processWhereExists($sql);
        return $this;
    }

    private function processWhereExists(string|callable $sql, bool $not = false, bool $or = false): void
    {
        if (is_callable($sql)) {
            $query = new static();
            $sql = $sql($query->toSql()->table($this->table)->select(1));

            if (gettype($sql) != 'string') {
                throw new \Exception('must be string');
            }

            $this->bindings = array_merge($this->bindings, $query->bindings);
        } else {
            if ($not) {
                $sql = "NOT ($sql)";
            }
            if (!$or) {
                $this->andWheres[] = $sql;
            } else {
                $this->orWheres[] = $sql;
            }
        }

        $sql = "EXISTS ($sql)";

        if ($not) {
            $sql = "NOT $sql";
        }

        if (!$or) {
            $this->andWheres[] = $sql;
        } else {
            $this->orWheres[] = $sql;
        }
    }

    public function toSql(bool $state = true): static
    {
        $this->toSqlStatus = $state;
        return $this;
    }




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

    private function buildSelect(): array
    {
        return array_merge(
            $this->buildSelectBase(),
            [$this->buildWhereClause()],
            $this->buildGroupBy(),
            $this->buildOrderBy(),
            $this->buildLimit(),
            $this->buildOffset()
        );
    }

    private function buildSelectBase(): array
    {
        $sql = ["SELECT"];
        if ($this->distinct) {
            $sql[] = "DISTINCT";
        }
        $sql[] = empty($this->selects) ? "*" : implode(', ', $this->selects);
        $sql[] = "FROM {$this->table}";
        return $sql;
    }

    private function buildGroupBy(): array
    {
        if ($this->groupBys) {
            return ["GROUP BY ".implode(', ', $this->groupBys)];
        }
        return [];
    }


    /**
     * Fetch Methods
     * ------------
     */

    public function get(): false|array|string
    {
        $sql = $this->setAction('select')->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt);
        $stmt->execute();
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
        $this->bind($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all(): false|array
    {
        $sql = $this->setAction('select')
            ->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count()
    {
        $sql = $this->setAction('select')
            ->select(['COUNT(*) AS count'])
            ->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
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

    public function insert(array ...$dataArrays): bool
    {
        $this->addInsertArray($dataArrays);
        $sql = $this->setAction('insert')->buildSql();
        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            vamp("Error: ".$e->getMessage());
            return false;
        }
    }

    private function addInsertArray($data): void
    {
        $this->insertArrays = array_merge($this->insertArrays, $data);
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
        return "( ".implode(',', array_keys($valueSet))." )";
    }

    private function buildInsertValues($i, $valueSet): string
    {
        foreach ($valueSet as $j => $value) {
            $placeholder = self::VALUE_BINDER."{$i}_{$j}";
            $this->bindings[$placeholder] = $value;
        }

        return "VALUES (".implode(',', array_keys($this->bindings)).")";
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
                $placeholder = self::UPDATE_BINDER."{$index}_{$column}";
                $updateParts[] = "{$column} = {$placeholder}";
                $this->bindings[$placeholder] = $value;
            }
            return " ON DUPLICATE KEY UPDATE ".implode(', ', $updateParts);
        }
        return null;
    }

    /**
     * UPDATE METHODS
     * ----------------
     */

    public function update(array $dataArrays)
    {
        $this->addUpdateArray($dataArrays);
        $sql = $this->setAction('update')->buildSql();

        if ($this->toSqlStatus) {
            return $sql;
        }

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt);

        try {
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            vamp("Error: ".$e->getMessage());
            return false;
        }
    }

    public function join(string $table, string $column1, string $operator, string $column2): static
    {
        $this->join = [
            'table' => $table,
            'from' => $column1,
            'operator' => $operator,
            'to' => $column2,
        ];
        return $this;
    }

    private function addUpdateArray($data): void
    {
        $this->updateArrays = array_merge($this->updateArrays, $data);
    }

    private function buildUpdateBase(): string
    {
        return "UPDATE {$this->table}";
    }

    private function buildJoin()
    {
        $j = $this->join;
        return "JOIN {$j['table']} ON {$j['from']} {$j['operator']} {$j['to']}";
    }

    private function buildSet()
    {
        $sql = [];
        foreach ($this->updateArrays as $column => $newValue) {
            $sql[] = "$column = {$newValue}";
        }
        // additional case end
        // additional raw
        return "SET ".implode(', ', $sql);
    }

    private function buildUpdate(): array
    {
        if (empty($this->updateArrays)) {
            throw new \Exception('update() argument must be filled');
        }

        $sql = [];

        $sql[] = $this->buildUpdateBase();
        if (!empty($this->join)) {
            $sql[] = $this->buildJoin();
        }
        $sql[] = $this->buildSet();
        $sql[] = $this->buildWhereClause();
        $limit = $this->buildLimit();
        if (!empty($limit)) {
            $sql[] = $limit[0];
        }

        return $sql;
    }


    /**
     * PRIVATE METHODS
     * ============
     * only used in this class
     */

    private function buildSql(): string
    {
        $sql = [];

        switch ($this->action) {
            case 'select':
                $sql = $this->buildSelect();
                break;
            case 'insert':
                $sql = $this->buildInsert();
                break;
            case 'update':
                $sql = $this->buildUpdate();
                break;
            case 'delete':
                break;
            default:
                break;
        }
        return implode(' ', $sql);
    }

    private function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    private function buildWhereClause(bool $includeWhereKeyword = true): ?string
    {
        $sql = [];

        if ($includeWhereKeyword && (!empty($this->andWheres) || !empty($this->orWheres))) {
            $sql[] = "WHERE";
        }

        if (!empty($this->andWheres)) {
            $sql[] = implode(' AND ', $this->andWheres);
        }
        if (!empty($this->orWheres)) {
            if (!empty($this->andWheres)) {
                $sql[] = "OR";
            }
            $sql[] = implode(' OR ', $this->orWheres);
        }
        return implode(" ", $sql);
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

    private function bind(PDOStatement $statement): void
    {
        foreach ($this->bindings as $placeholder => $value) {
            $statement->bindValue($placeholder, $value);
        }
        $this->bindings = [];
    }

    private function randomPlaceholder(): int|string
    {
        return bin2hex(random_bytes(5));
//        return generateUUID("_");
    }

    private function getOperator(?string $operator = '='): string
    {
        if (is_null($operator)) {
            return false;
        }

        return match (strtolower($operator)) {
            '=', 'eq' => '=',
            '<>', 'neq' => '!=',
            '>', 'gt' => '>',
            '>=', 'gt-eq' => '>=',
            '<', 'lt' => '<',
            '<=', 'lt-eq' => '<=',
            'like' => 'LIKE',
            'is' => 'IS',
            'is not' => 'IS NOT',
            default => false
        };
    }
}
