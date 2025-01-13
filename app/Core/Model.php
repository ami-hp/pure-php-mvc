<?php

namespace App\Core;

use App\Core\Facades\DB;
use App\Core\Mysql;
use PDO;

class Model
{
    protected ?string $table;
    public string $primaryKey = 'id';

//    public function __construct()
//    {
//        $tableName = $this->getTableName();
//        parent::__construct($tableName);
//    }

    public function __construct()
    {
        if (!isset($this->table)) {
            $this->table = pluralize(strtolower((new \ReflectionClass($this))->getShortName()));
        }
    }

    protected function getTableName(): ?string
    {
        return property_exists($this, 'table') ? $this->table : null;
    }


    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    public static function query()
    {
        $instance = new static();
        return DB::table($instance->table);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public static function __callStatic($method, $args)
    {
        $instance = new static();
        return $instance->$method(...$args);
    }


}
