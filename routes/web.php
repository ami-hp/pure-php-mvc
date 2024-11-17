<?php

use App\Core\Route;

Route::get('/', [\app\Http\Controllers\HomeController::class , 'index']);


Route::dispatch();