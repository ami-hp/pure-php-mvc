<?php

namespace App\Core;

use app\Core\Utils\Singleton;

class View
{
    use Singleton;

    /**
     * @param  string $view
     * @param  array  $data
     * @return void
     */
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        $view = str_replace('.', '/', $view);
        require_once '../../resources/views/'.$view.'.php';
    }
}
