<?php

namespace app\Http\Controllers;

use App\Core\Facades\DB;
use app\Http\Controller;
use Throwable;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $users = DB::table('users')
                ->where('id' , '>' , 63)
                ->delete();

            vamp($users);

        } catch (Throwable $e) {
            vamp($e->getMessage());
            vamp($e->getTraceAsString());
        }

    }
}
