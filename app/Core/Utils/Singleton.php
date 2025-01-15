<?php

namespace app\Core\Utils;

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
