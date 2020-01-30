<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => "live_chat"], function ($router) {
    $router->post('authenticate','ChatkitController@chatkitAuthenticate');
    $router->get('users', 'ChatkitController@getAllUsers');
    $router->post('sendMessage','ChatkitController@sendMessage');
});