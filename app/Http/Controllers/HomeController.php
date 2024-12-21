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
            ->onDuplicateKeyUpdate(null,['username'=>'done_x'.rand(1,51)])
            ->insert(
                [
                    'name' => 'ami'.rand(1,51),
                    'username'=> 'test_x',
                    'password' => '123456',
                ],
                [
                    'username'=> 'test_x',
                    'password' => '123456',
                ],
            );
        vamp($insert);

//        $users = $userModel
//            ->select(['username' , 'password'])
//            ->get();
//
//        vamp($users);
    }
}
