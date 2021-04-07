<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Exchange;
use GTrader\Log;

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


    public function list(Request $request, array $options = [])
    {
        $options = array_replace_recursive([
                'get'       => ['self', 'configured'],
                'user_id'   => Auth::id(),
            ],
            $options
        );
        return response(
            Exchange::getList($options),
            200
        );
    }


    public function ESR(Request $request)
    {
        return response(
            json_encode(Exchange::getESR()),
            200
        );
    }


    public function symbols(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }
        try {
            $symbols = $exchange->getSymbols();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response($e->getMessage(), 300);
        }
        return response(json_encode($symbols), 200);
    }


    public function info(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }
        try {
            $info = $exchange->getInfo();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response($e->getMessage(), 300);
        }
        return response($info, 200);
    }


    public function form(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        return $exchange->form($config->options);
    }


    public function formSymbols(Request $request)
    {
        list($exchange, $config, $class, $error) = $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        return view('Exchanges/FormSymbols', [
            'exchange' => $exchange,
            'selected' => $config->options['symbols'] ?? [],
        ]);
    }


    public function addSymbol(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        if (!isset($request->new_symbol)|| !isset($request->new_res)) {
            Log::error('missing parameters');
            return response('Could not add symbol', 400);
        }

        $exchange->handleAddSymbolRequest(
            $config,
            $request->new_symbol,
            $request->new_res
        );

        return view('Exchanges/FormSymbols', [
            'exchange' => $exchange,
            'selected' => $config->options['symbols'] ?? [],
            'reload' => ['ESR']
        ]);
    }


    public function resRangeForm(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        if (!isset($request->symbol_id) || !isset($request->res)) {
            Log::error('missing parameters');
            return response('Could display resolution range form', 400);
        }
        $symbol_id = $request->symbol_id;
        $res = $request->res;
        if (!isset($exchange->getResolutions($symbol_id)[$res])) {
            Log::error('missing resolution', $exchange->getId(), $symbol_id, $res);
            return response('Could display resolution range form', 400);
        }

        return view('Exchanges/FormResRanges', [
            'exchange'      => $exchange,
            'symbol_id'     => $symbol_id,
            'res'           => $res,
            'num_candles'   => $exchange->getNumCandles($symbol_id, $res),
            'candles_start' => $exchange->getFirstCandleTime($symbol_id, $res),
            'candles_end'   => $exchange->getLastCandleTime($symbol_id, $res),
            'range_start'   => $exchange->globalEpoch($symbol_id, $res),
            'range_end'     => $exchange->globalEnd($symbol_id, $res),
        ]);
    }


    public function resRangeUpdate(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        if (!isset($request->symbol_id) || !isset($request->res)) {
            Log::error('missing parameters');
            return response('Could update ranges', 400);
        }
        $symbol_id = $request->symbol_id;
        $res = $request->res;
        if (!isset($exchange->getResolutions($symbol_id)[$res])) {
            Log::error('missing resolution', $exchange->getId(), $symbol_id, $res);
            return response('Could update ranges', 400);
        }

        foreach (['start', 'end'] as $pos) {
            ${'enable_'.$pos} = false;
            if (isset($request->{'enable_'.$pos}) && $request->{'enable_'.$pos}) {
                if (isset($request->{'range_'.$pos}) && strlen($request->{'range_'.$pos})) {
                    $d = date_parse_from_format('Y-m-d', $request->{'range_'.$pos});
                    ${'range_'.$pos} = mktime(0, 0, 0, $d['month'], $d['day'], $d['year']);
                    ${'enable_'.$pos} = true;
                    Log::debug('update ', $pos, 'from', $exchange->{'global'.('start' === $pos ? 'Epoch' : 'End')}($symbol_id, $res), 'to', ${'range_'.$pos});
                }
            }
            $exchange->{'global'.('start' === $pos ? 'Epoch' : 'End')}(
                $symbol_id,
                $res,
                ${'enable_'.$pos} ? ${'range_'.$pos} : 0
            );
            Log::debug('new', $pos, $exchange->{'global'.('start' === $pos ? 'Epoch' : 'End')}($symbol_id,$res));
        }

        return $exchange->form($config->options);
    }


    public function deleteRes(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        if (!isset($request->symbol) || !isset($request->res)) {
            Log::error('missing parameters');
            return response('Could not delete resolution', 400);
        }

        $exchange->handleDeleteResolutionRequest(
            $config,
            $request->symbol,
            $request->res
        );

        return view('Exchanges/FormSymbols', [
            'exchange' => $exchange,
            'selected' => $config->options['symbols'] ?? [],
            'reload' => ['ESR'],
        ]);
    }


    public function deleteSymbol(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }

        if (!isset($request->symbol)) {
            Log::error('missing parameter');
            return response('Could not delete symbol', 400);
        }

        $exchange->handleDeleteSymbolRequest(
            $config,
            $request->symbol
        );

        return view('Exchanges/FormSymbols', [
            'exchange' => $exchange,
            'selected' => $config->options['symbols'] ?? [],
            'reload' => ['ESR'],
        ]);
    }


    public function save(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }
        if (isset($request->options)) {
            //Log::debug($request->options);
        }
        $exchange->handleSaveRequest($request, $config);
        return $this->list($request, ['reload' => ['ESR']]);
    }


    public function delete(Request $request)
    {
        list($exchange, $config, $class, $error) =
                $this->setUpRequest($request);
        if ($error) {
            return response($error);
        }
        $config->delete();
        return $this->list($request, ['reload' => ['ESR']]);
    }


    private function setUpRequest(Request $request)
    {
        $exchange = $config = $error = null;
        $exchange_id = (int)$request->id;
        $class = $request->class;
        if ($class && $exchange_id) {
            Log::error('we need either class or id, not both');
            $error = response('Failed to load exchange.', 404);
        }
        if (!$error) {
            if ($exchange_id) {
                if (!($class = Exchange::getNameById($exchange_id))) {
                    Log::error('Failed to get exchange name from id '.$exchange_id);
                    $error = response('Failed to load exchange.', 404);
                }
            } elseif (!$class) {
                Log::error('no id or class');
                $error = response('Failed to load exchange.', 404);
            }
        }
        if (!$error) {
            if (!$exchange = Exchange::make($class, $request->options ?? [])) {
                Log::error('Failed to make '.$class);
                $error = response('Failed to load exchange.', 404);
            }
        }
        if (!$error) {
            if (!$exchange_id = $exchange->getId()) {
                Log::error('Failed to get  id for '.$class);
                $error = response('Failed to load exchange.', 404);
            }
        }
        if (!$error) {
            $config = Auth::user()
                ->exchangeConfigs()
                ->firstOrNew(['exchange_id' => $exchange_id]);
            $options = $config->options ?? [];
            foreach ($exchange->getParam('user_options') ?? [] as $key => $default) {
                if (!isset($options[$key])) {
                    $options[$key] = $default;
                }
            }
            $config->options = $options;
        }
        return [$exchange, $config, $class, $error];
    }
}
