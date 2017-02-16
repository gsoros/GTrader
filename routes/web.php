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



Auth::routes();

//Route::get('/', function () {
//    return view('dashboard');
//});

Route::get('/',                 'HomeController@dashboard');
Route::get('/plot.json',        'ChartController@JSON');
Route::get('/settings.form',    'ChartController@settingsForm');
Route::get('/indicator.form',   'ChartController@indicatorForm');
Route::get('/indicator.new',    'ChartController@indicatorNew');
Route::get('/indicator.delete',  'ChartController@indicatorDelete');
Route::get('/indicator.save',   'ChartController@indicatorSave');
