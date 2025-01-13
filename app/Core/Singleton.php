<?php

namespace App\Core;

use App\Core\Base;

trait Singleton
{
    use Base;
    protected static mixed $instance = null;
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
