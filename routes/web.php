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

Route::get('/strategy.train',           'StrategyController@train');
Route::get('/strategy.trainStart',      'StrategyController@trainStart');
Route::get('/strategy.trainStop',       'StrategyController@trainStop');
Route::get('/strategy.trainProgress',   'StrategyController@trainProgress');

Route::get('/bot.list',                 'BotController@list');
Route::get('/bot.new',                  'BotController@new');
Route::get('/bot.form',                 'BotController@form');
Route::get('/bot.delete',               'BotController@delete');
Route::post('/bot.save',                'BotController@save');


