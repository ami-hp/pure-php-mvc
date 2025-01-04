<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;
use Throwable;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $userModel = new User();

            $users = $userModel
                ->query()
                ->whereNot('username', '=', 'guest')
                ->orWhereExists(function ($query) use ($userModel){
                    $query->table($userModel->getTable());
                    $query->where(function ($query) {
                        $query->whereNotIn('id', [1, 2]);
                    });
                    $query->whereNotBetween('id', 54, 60);
                    return $query->toSql()->get();
                })
                ->where('name', 'LIKE', '%ami%')
                ->get();

            vamp($users);

        } catch (Throwable $e) {
            vamp($e->getMessage());
            vamp($e->getTraceAsString());
        }

    }
}
