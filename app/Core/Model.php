<?php

namespace App\Core;

use App\Core\Mysql;
use PDO;

class Model extends MysqlQueryBuilder
{
    protected string $table;
    public $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }


    public function setPrimaryKey(string $primaryKey) : void
    {
        $this->primaryKey = $primaryKey;
    }

    public function query(): MysqlQueryBuilder
    {
        return (new MysqlQueryBuilder())->table($this->table);
    }





}