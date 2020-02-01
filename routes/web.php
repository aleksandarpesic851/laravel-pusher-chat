<?php

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

Auth::routes();

Route::middleware(["auth"])->group(function() {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/client_chat', 'HomeController@clientChat')->name('client_chat');
    Route::group(['prefix' => "live_chat"], function ($router) {
        $router->get('/', 'ChatkitController@chat')->name('chat');
    });
});