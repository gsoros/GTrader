<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Http\Request;

use GTrader\UserExchangeConfig;
use GTrader\Log;

class okex3 extends Supported
{
    public function __construct(array $params = [])
    {
        //$this->setParam('cache.log', 'all');
        //$this->setParam('pcache.log', 'all');

        parent::__construct($params);

        if ($pw = $this->getUserOption('apiKeyPassword')) {
            if (!$this->setCCXTProperty('password', $pw)) {
                throw new \Exception('Could not set API Key Password');
            }
        }
    }


    public function handleSaveRequest(Request $request, UserExchangeConfig $config)
    {
        $r_options = $request->options ?? [];
        $c_options = $config->options ?? [];
        foreach (['apiKeyPassword'] as $param) {
            if (isset($r_options[$param])) {
                $c_options[$param] = $r_options[$param];
            }
        }
        $config->options = $c_options;
        return parent::handleSaveRequest($request, $config);
    }
}
