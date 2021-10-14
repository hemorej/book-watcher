<?php

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

Auth::routes(['register' => false]);
Route::get('/home', 'HomeController@index')->name('home');