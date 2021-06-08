<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');



Route::get('/login/facebook', 'App\Http\Controllers\Auth\LoginController@redirectToFacebookProvider');
Route::get('login/facebook/callback', 'App\Http\Controllers\Auth\LoginController@handleProviderFacebookCallback');

Route::group(['middleware' => [
    'auth'
]], function(){
	
	Route::get('/user', 'App\Http\Controllers\GraphController@retrieveUserProfile');
	
	/*
	Route::post('/user', 'App\Http\Controllers\GraphController@publishToProfile');
	Route::get('/profile', 'App\Http\Controllers\GraphController@publishToProfile');
	Route::post('/page', 'App\Http\Controllers\GraphController@publishToPage');
	*/
	/*
    Route::get('/user', 'GraphController@retrieveUserProfile');
    Route::post('/user', 'GraphController@publishToProfile');
    Route::get('/profile', 'GraphController@publishToProfile');
    Route::get('/page', 'GraphController@publishToPage');
	*/
});


