<?php

namespace app\Http\Controllers;

use App\Core\Facades\DB;
use App\Core\MysqlQueryBuilder;
use app\Http\Controller;
use App\Models\User;
use Throwable;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $users = User::query()->all();

            vamp($users);

        } catch (Throwable $e) {
            vamp($e->getMessage());
            vamp($e->getTraceAsString());
        }

    }
}
