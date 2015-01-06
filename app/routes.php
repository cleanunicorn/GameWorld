<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', 'HomeController@getHome');
Route::get('/login', 'HomeController@getLogin');
Route::get('/logged', 'HomeController@getLogged');
Route::get('/friends/{SteamId}', 'HomeController@getFriends');
Route::get('/friends-games/{SteamId}', 'HomeController@getFriendsGames');
Route::get('/games/{SteamId}', 'HomeController@getGames');

Route::get('/recommend/by-friends/{SteamId}', 'RecommendController@getByFriends');
