<?php

namespace app\Http;

use App\Core\Model;
use App\Core\View;

class Controller
{
    protected function model($model) : Model
    {
        require_once '../../app/Models/'.$model.'.php';
        return new $model();
    }

    protected function view($view, $data = []): void
    {
        View::render($view, $data);
    }
}