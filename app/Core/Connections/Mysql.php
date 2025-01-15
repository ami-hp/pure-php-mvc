<?php

namespace app\Core\Connections;

class Mysql extends \PDO
{
    use \app\Core\Utils\Singleton;

    public function __construct()
    {
        $config = require __DIR__.'/../../../config/database.php';
        $mysql = $config['mysql'];

        $aDriverOptions[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES UTF8';
        parent::__construct(
            'mysql:host=' . $mysql['host'] . ';dbname=' . $mysql['db_name'] . ';',
            $mysql['username'],
            $mysql['password'],
            $aDriverOptions
        );
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    }

    protected static mixed $instance = null;
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
