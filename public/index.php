<?php

require_once __DIR__.'/../vendor/autoload.php';


require_once '../bootstrap/autoloader.php';
require_once '../config/database.php';
new \app\Core\Connections\Mysql();
require_once '../routes/web.php'; // Initialize the application new \App\Core\Database();

