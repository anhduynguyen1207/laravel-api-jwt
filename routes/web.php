<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/check-redis', function () {
    try {
        Redis::set('test', 'connected');
        return 'Redis Connected: ' . Redis::get('test');
    } catch (\Exception $e) {
        return 'Redis Connection Failed: ' . $e->getMessage();
    }
});
