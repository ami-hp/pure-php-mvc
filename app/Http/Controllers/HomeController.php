<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $userModel = new User();

        $insert = $userModel
            ->insertIgnore()
            ->columns(['name' , 'username', 'password'])
            ->values(['ami'.rand(1,51) , 'ami_hp'.rand(1,51) , '123456'])
            ->insert();
        vamp($insert);

        $users = $userModel
            ->select(['username' , 'password'])
            ->get();

        vamp($users);
    }
}
