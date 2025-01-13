<?php

namespace App\Core\Facades;

use App\Core\Services\DatabaseService;

/**
 * @method table(string $name)
 * @method toSql()
 * -------------------
 * @method select()
 * @method groupBy()
 * @method orderBy()
 * @method limit()
 * @method offset()
 * @method unique()
 * @method join()
 * --------------------
 * @method where()
 * @method whereNot()
 * @method orWhere()
 * @method orWhereNot()
 * @method whereNotExists()
 * @method whereExists()
 * @method orWhereExists()
 * @method orWhereNotExists()
 * @method whereColumn()
 * @method orWhereColumn()
 * @method whereAny()
 * @method whereNotAny()
 * @method orWhereAny()
 * @method orWhereNotAny()
 * @method whereAll()
 * @method whereNone()
 * @method orWhereAll()
 * @method orWhereNone()
 * @method whereNull()
 * @method orWhereNull()
 * @method whereNotNull()
 * @method orWhereNotNull()
 * @method whereIn()
 * @method whereNotIn()
 * @method whereBetween()
 * @method whereNotBetween()
 * @method whereBetweenColumn()
 * @method whereNotBetweenColumn()
 * ------------------------
 * @method first()
 * @method all()
 * @method get()
 * @method count()
 * ------------------------
 * @method insertIgnore()
 * @method onDuplicateKeyUpdate()
 * @method insert()
 * --------------------------
 * @method update()
 */
class DB
{
    protected static ?DatabaseService $instance = null;
    protected static ?string $connection = null;
    public static function getInstance(): DatabaseService
    {
        if (!static::$instance) {
            $database = static::$connection ?? config('database.database');
            static::$instance = new DatabaseService($database);
        }
        return static::$instance;
    }

    //todo needs to be dynamic
    public static function connection($database): DatabaseService
    {
        static::$connection = $database;
        static::$instance = new DatabaseService($database);
        return static::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return static::getInstance()->$method(...$args);
    }
}