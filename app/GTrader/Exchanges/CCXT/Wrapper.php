<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;

use GTrader\UserExchangeConfig;
use GTrader\Exchange;
use GTrader\Trade;
use GTrader\Log;
use ccxt\Exchange as CCXTlib;

class Wrapper extends Exchange
{
    use HasCCXT;

    protected const CLASS_PREFIX = 'CCXT\\';


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        if ($ccxt_id = $this->getParam('ccxt_id')) {
            $this->ccxt($ccxt_id);
        }
        //$this->setParam('pcache.log', 'all');
        //Log::debug($this->getParam('default_child', 'no default_child'));
    }


    public function getId()
    {
        return self::getOrAddIdByName(
            $this->getVirtualClassName(),
            $this->getLongName()
        );
    }


    public function getVirtualClassName()
    {
        return self::CLASS_PREFIX.$this->getShortClass();
    }


    public function getSupported(array $options = []): array
    {
        $exchanges = [];

        $get = Arr::get($options, 'get', ['self']);
        $all = in_array('all', $get);
        $self = in_array('self', $get);
        $configured = in_array('configured', $get);
        $active = in_array('active', $get);
        $user_id = Arr::get($options, 'user_id');
        $name = Arr::get($options, 'name');

        //Log::debug($options, $all, $self, $configured, $user_id);

        if ($self) {
            $exchanges[] = $this;
        }

        if ($all || $configured) {
            $CCXTlib = new CCXTlib;
            $blacklist = $this->getParam('blacklist', []);
            foreach ($CCXTlib::$exchanges as $ccxt_id) {
                if (in_array($ccxt_id, $blacklist)) {
                    continue;
                }
                $class_name = self::CLASS_PREFIX.$ccxt_id;
                if ($name && ($name !== $class_name)) {
                    continue;
                }
                //Log::debug('making '.self::CLASS_PREFIX.$ccxt_id);
                $exchange = self::make($class_name, ['ccxt_id' => $ccxt_id]);
                if ($configured) {
                    $config = UserExchangeConfig::select('options');
                    if ($user_id) {
                        $config->where('user_id', $user_id);
                    }
                    $config->where('exchange_id', $exchange->getId());
                    if (!$config->value('options')) {
                        continue;
                    }
                }
                $exchanges[] = $exchange;
            }
            //$exchanges = array_slice($exchanges, 0, 20);
        }

        return $exchanges;
    }


    public function form(array $options = [])
    {
        $exchanges = $this->getSupported([
            'get' => ['all'],
        ]);
        $ids = [];
        foreach ($exchanges as $exchange) {
            $ids[] = $exchange->getParam('ccxt_id');
        };

        return view('Exchanges/CCXT/WrapperForm', [
            'exchange'              => $this,
            'supported_exchanges'   => $exchanges,
            'supported_exchange_ids' => $ids,
        ]);
    }


    public function getListItem()
    {
        return view('Exchanges/CCXT/WrapperListItem', ['exchange' => $this]);
    }


    public function getName()
    {
        return 'CCXT';
    }


    public function getLongName()
    {
        return 'CCXT';
    }


    public function getClassPrefix()
    {
        return self::CLASS_PREFIX;
    }

}
