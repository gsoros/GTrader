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

if (\Config::get('app.env') === 'local') {
    Route::get('/deletechart', 'ChartController@delete');
    Route::get('/test', 'HomeController@test');
    Route::get('/phpinfo', function() {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        return $phpinfo;
    });
}

Route::get('/',                         'HomeController@dashboard');

//Route::get('/plot.json',                'ChartController@JSON');
Route::get('/plot.image',               'ChartController@image');
Route::get('/settings.form',            'ChartController@settingsForm');
Route::get('/strategy.selectorOptions', 'ChartController@strategySelectorOptions');
Route::get('/strategy.select',          'ChartController@strategySelect');

Route::get('/indicator.form',           'IndicatorController@form');
Route::get('/indicator.new',            'IndicatorController@create');
Route::get('/indicator.delete',         'IndicatorController@delete');
Route::post('/indicator.save',          'IndicatorController@save');

Route::get('/strategy.list',            'StrategyController@list');
Route::get('/strategy.new',             'StrategyController@create');
Route::get('/strategy.form',            'StrategyController@form');
Route::get('/strategy.delete',          'StrategyController@delete');
Route::post('/strategy.save',           'StrategyController@save');

Route::get('/strategy.train',           'StrategyController@train');
Route::get('/strategy.trainStart',      'StrategyController@trainStart');
Route::get('/strategy.trainStop',       'StrategyController@trainStop');
Route::get('/strategy.trainProgress',   'StrategyController@trainProgress');
Route::get('/strategy.trainHistory',    'StrategyController@trainHistory');
Route::get('/strategy.trainPause',      'StrategyController@trainPause');
Route::get('/strategy.trainResume',     'StrategyController@trainResume');
Route::get('/strategy.sample',          'StrategyController@sample');

Route::get('/bot.list',                 'BotController@list');
Route::get('/bot.new',                  'BotController@create');
Route::get('/bot.form',                 'BotController@form');
Route::get('/bot.delete',               'BotController@delete');
Route::post('/bot.save',                'BotController@save');

Route::get('/exchange.form',            'ExchangeController@form');
Route::get('/exchange.list',            'ExchangeController@list');
Route::post('/exchange.save',           'ExchangeController@save');
