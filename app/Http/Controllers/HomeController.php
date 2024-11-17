<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $userModel = new User();
        $users = $userModel->all();
        echo " aaaaaa";
        vamp($users);
    }
}