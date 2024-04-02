<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('bots')->group(function () {
    Route::any('hook/{id}', "App\Http\Controllers\Bots\HookController@index");
    Route::any('test/setCommands', "App\Http\Controllers\Bots\TestController@setCommands");
    Route::any('test/pa', "App\Http\Controllers\Bots\TestController@pa");
    Route::any('test/sendC', "App\Http\Controllers\Bots\TestController@sendC");
    Route::any('test/webapp', "App\Http\Controllers\Bots\TestController@webapp");
    Route::any('test/webapp_hook', "App\Http\Controllers\Bots\TestController@webapp_hook");
});
