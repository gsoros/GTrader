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

Route::get('/',                         'HomeController@dashboard');

//Route::get('/plot.json',                'ChartController@JSON');
Route::get('/plot.image',               'ChartController@image');
Route::get('/settings.form',            'ChartController@settingsForm');
Route::get('/strategy.selectorOptions', 'ChartController@strategySelectorOptions');
Route::get('/strategy.select',          'ChartController@strategySelect');
Route::get('/indicator.form',           'ChartController@indicatorForm');
Route::get('/indicator.new',            'ChartController@indicatorNew');
Route::get('/indicator.delete',         'ChartController@indicatorDelete');
Route::post('/indicator.save',          'ChartController@indicatorSave');

Route::get('/strategy.list',            'StrategyController@list');
Route::get('/strategy.new',             'StrategyController@new');
Route::get('/strategy.form',            'StrategyController@form');
Route::get('/strategy.delete',          'StrategyController@delete');
Route::post('/strategy.save',           'StrategyController@save');
Route::get('/strategy.trainForm',       'StrategyController@trainForm');
Route::get('/strategy.trainStart',      'StrategyController@trainStart');




