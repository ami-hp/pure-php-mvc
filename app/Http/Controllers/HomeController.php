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
            ->limit(1)
            ->groupBy('username')
            ->where('id' , '=' , '1')
            ->select(['username' , 'password'])
            ->limit(1)
            ->get();

        vamp($users);
    }
}
