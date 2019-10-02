<?php

namespace GTrader\Exchanges;

use Illuminate\Support\Arr;

use GTrader\Exchange;
use GTrader\Trade;
use GTrader\Log;
use ccxt\Exchange as CCXT;

class CCXTWrapper extends Exchange
{
    protected const CCXT_NAMESPACE = '\\ccxt\\';
    protected $ccxt = null;
    

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        if ($id = $this->getParam('id')) {
            $this->ccxt($id);
        }
        //Log::debug($this->getParam('id', 'no id'));
    }


    protected function ccxt(string $id = '', bool $temporary = false)
    {
        $id = strlen($id) ? $id : $this->getParam('ccxt_id');
        $id = strlen($id) ? $id : null;

        $make_ccxt = function (string $id) {
            if (!$id) {
                throw new \Exception('Tried to make ccxt without id');
                return null;
            }
            $class = self::CCXT_NAMESPACE.$id;
            if (!class_exists($class)) {
                throw new \Exception($class.' does not exist');
                return null;
            }
            return new $class;
        };

        if ($temporary) {
            if (is_object($this->ccxt) && $this->ccxt->id == $id) {
                return $this->ccxt;
            }
            return $make_ccxt($id);
        }
        if (!is_object($this->ccxt) ||
            (
                $id &&
                is_object($this->ccxt) &&
                $this->ccxt->id !== $id
            )) {

            if ($id && is_object($this->ccxt) && $this->ccxt->id !== $id) {
                Log::debug('a different ccxt exists', $this->oid(), $this->ccxt->id, $id);
            }


            if (is_object($this->ccxt)) {
                $this->cleanCache();
            }
            $this->ccxt = $make_ccxt($id);
        }
        return $this->ccxt;
    }

    public function getSupported(array $options = []): array
    {
        // all exchanges supported by ccxt
        if (true === Arr::get($options, 'get_all')) {
            $CCXT = new CCXT;
            $exchanges = [];
            foreach ($CCXT::$exchanges as $id) {
                $exchange = $this->ccxt($id, true);
                $exchanges[] = $exchange;
            }
            return $exchanges;
        }

        // exchanges chosen by the user

        return [$this];
    }


    public function form(array $options = [])
    {
        $exchanges = $this->getSupported(['get_all' => true]);
        $supported = [];
        foreach ($exchanges as $exchange) {
            $supported[] = [
                'id' => $exchange->id,
                'name' => $exchange->name ?? 'unnamed',
            ];
        }
        //$supported = array_slice($supported, 0, 7);
        $selected = ['anxpro'];
        return view('Exchanges/CCXTWrapper_form', [
            'exchange' => $this,
            'supported_exchanges' => $supported,
            'selected_exchanges' => $selected,
        ]);
    }


    public function getSymbols(array $options = []): array
    {
        if (!$symbols = $this->cached('symbols')) {
            $this->ccxt()->loadMarkets();
            $this->cache('symbols', $this->ccxt()->symbols ?? []);
        }
        return $this->cached('symbols');
    }


    public function getTicker(string $symbol) {}
    public function getCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    ) {}
    public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    ) {}
    public function cancelUnfilledOrders(string $symbol, int $before_timestamp) {}
    public function saveFilledOrders(string $symbol, int $bot_id = null) {}

}
