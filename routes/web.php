<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', [
	'as' => 'index',
	'middleware' => ['web', 'auth'],
	'uses' => 'BookController@index'
]);

Route::post('/urls/create', [
	'middleware' => ['web', 'auth'],
	'uses' => 'BookController@create'
]);

Route::post('/delete', [
	'middleware' => ['web', 'auth'],
	'uses' => 'BookController@delete'
]);

Route::post('/check', [
	'middleware' => ['web', 'auth'],
	'uses' => 'BookController@check'
]);

Auth::routes();

Route::get('/home', 'HomeController@index');
