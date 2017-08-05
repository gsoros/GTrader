<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Exchange;

class ExchangeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function list(Request $request)
    {
        return response(Exchange::getList(), 200);
    }


    public function form(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return $error;
        }

        return $exchange->form($config->options);
    }



    public function save(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        $options = $config->options;
        foreach ($exchange->getParam('user_options') as $key => $default) {
            $options[$key] = $request->$key ?? $default;
        }
        $config->options = $options;
        $config->save();

        return response(Exchange::getList(), 200);
    }


    private function setUpRequest(Request $request)
    {
        $exchange = $config = $class = $error = null;
        $exchange_id = (int)$request->id;
        if (!($class = Exchange::getNameById($exchange_id))) {
            error_log('Failed to load exchange ID '.$exchange_id);
            $error = response('Failed to load exchange.', 404);
        } else {
            $exchange = Exchange::make($class);
            $config = Auth::user()
                        ->exchangeConfigs()
                        ->firstOrNew(['exchange_id' => $exchange_id]);
            $options = $config->options;
            foreach ($exchange->getParam('user_options') as $key => $default) {
                if (!isset($options[$key])) {
                    $options[$key] = $default;
                }
            }
            $config->options = $options;
        }
        return [$exchange, $config, $class, $error];
    }
}
