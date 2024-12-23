<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $userModel = new User();

            $users = $userModel
                ->query()
                ->where('username', '=', 'ami_hp')
                ->orWhere(function ($query) {
                    $query->where('id', '>', 85)
                        ->orWhere(function ($query) {
                            $query->whereIn('id', [1, 2]);
                        })
                        ->whereIn('id', [1, 5]);
                })
                ->toSql()
                ->where('name', 'LIKE', 'ami')
                ->get();

            vamp($users);

        } catch (Throwable $e) {
            vamp($e->getMessage());
            vamp($e->getTraceAsString());
        }

    }
}
