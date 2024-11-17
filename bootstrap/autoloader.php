<?php

spl_autoload_register(function ($className) {
    $class = str_replace('\\', '/', $className);
    $file = __DIR__.'/../'.$class.'.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
