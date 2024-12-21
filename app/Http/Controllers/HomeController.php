<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $userModel = new User();



        $users = $userModel
            ->query()
            ->select(['username' , 'password'])
            ->orderBy(['username' => 'asc' , 'password' => 'asc'])
            ->limit(5)
            ->get();

        vamp($users);

        $users = $userModel
            ->query()
            ->select(['username' , 'password'])
            ->first();

        vamp($users);
    }
}
