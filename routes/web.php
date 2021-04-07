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
    Route::get('/chart.delete', 'ChartController@delete');
    Route::get('/chart.dump',   'ChartController@dump');
    Route::get('/dev',          'DevController@dev');
    Route::get('/dev.dev',      'DevController@dev');
    Route::get('/dev.vis',      'DevController@vis');
    Route::get('/dev.pcache',   'DevController@pcache');
    Route::get('/dev.json',     'DevController@json');
    Route::get('/dev.dumps',    'DevController@dumps');
    Route::get('/dev.dist',     'DevController@dist');
    Route::get('/dev.dump',     'DevController@dump');
    Route::get('/dump',         'DevController@dump');
    Route::get('/test',         'DevController@test');
    Route::get('/phpinfo',      'DevController@phpinfo');
}

Route::get('/',                         'HomeController@dashboard');

//Route::get('/plot.json',                'ChartController@JSON');
Route::get('/plot.image',               'ChartController@image');
Route::get('/settings.form',            'ChartController@settingsForm');
Route::get('/strategy.selectorOptions', 'ChartController@strategySelectorOptions');
Route::get('/strategy.select',          'ChartController@strategySelect');

Route::get('/indicator.form',           'IndicatorController@form');
Route::post('/indicator.form',          'IndicatorController@form');
Route::get('/indicator.new',            'IndicatorController@create');
Route::get('/indicator.delete',         'IndicatorController@delete');
Route::post('/indicator.delete',        'IndicatorController@delete');
Route::post('/indicator.save',          'IndicatorController@save');
Route::post('/indicator.toggleMutable', 'IndicatorController@toggleMutable');
Route::get('/indicator.sources',        'IndicatorController@sources');

Route::get('/strategy.list',            'StrategyController@list');
Route::get('/strategy.new',             'StrategyController@create');
Route::get('/strategy.form',            'StrategyController@form');
Route::get('/strategy.clone',           'StrategyController@clone');
Route::get('/strategy.delete',          'StrategyController@delete');
Route::post('/strategy.save',           'StrategyController@save');

Route::get('/strategy.Simple.signalsForm', 'StrategyController@simpleSignalsForm');

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
Route::get('/exchange.formSymbols',     'ExchangeController@formSymbols');
Route::get('/exchange.addSymbol',       'ExchangeController@addSymbol');
Route::get('/exchange.resRangeForm',    'ExchangeController@resRangeForm');
Route::post('/exchange.resRangeUpdate', 'ExchangeController@resRangeUpdate');
Route::get('/exchange.deleteRes',       'ExchangeController@deleteRes');
Route::get('/exchange.deleteSymbol',    'ExchangeController@deleteSymbol');
Route::get('/exchange.ESR',             'ExchangeController@ESR');
Route::get('/exchange.symbols',         'ExchangeController@symbols');
Route::get('/exchange.info',            'ExchangeController@info');
Route::get('/exchange.list',            'ExchangeController@list');
Route::post('/exchange.save',           'ExchangeController@save');
Route::get('/exchange.delete',          'ExchangeController@delete');

// Route::get('/password.change', function() { return view('auth.passwords.change'); });
Route::get('/password.change', 'Auth\ChangePasswordController@view');
Route::post('/password.change', 'Auth\ChangePasswordController@change');
