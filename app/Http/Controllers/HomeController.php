<?php

namespace app\Http\Controllers;

use app\Http\Controller;
use App\Models\User;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
       try{
           $userModel = new User();

           $users = $userModel
               ->query()
               ->where('id', '>' , '1')
               ->where('id', '<' , '4')
               ->orderBy(['username' => 'asc' , 'password' => 'asc'])
               ->limit(5)
               ->toSql()
               ->update([
                   'name' => json_encode('updated_to_me'),
                   'password' => json_encode('random')
               ]);

           vamp($users);


       } catch (Throwable $e) {
           vamp($e->getMessage());
           vamp($e->getTraceAsString());
       }

    }
}
