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

Route::get('/paypaldemo', 'HomeController@paypaldemo')->name('paypaldemo');
Route::get('/createorder', 'HomeController@createOrder')->name('createorder');

//Route::get('/execute', 'PaymentControler@execute')->name('execute');
Route::get('/{payerid}/{paymentid}/capture', 'HomeController@executePayment')->name('execute');
	
Route::get('/', function () {
    return view('welcome');

});
