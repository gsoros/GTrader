<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Http\Request;

use GTrader\UserExchangeConfig;
use GTrader\Log;

class kucoin extends Supported
{
    public function __construct(array $params = [])
    {
        //$this->setParam('cache.log', 'all');
        //$this->setParam('pcache.log', 'all');

        parent::__construct($params);

        if ($this->getUserOption('use_sandbox')) {
            if (!$test_url = $this->getSandboxApiUrl()) {
                throw new \Exception('Could not obtain sandbox API URL');
            }
            if (!$this->setCCXTProperty(['urls', 'api'], $test_url)) {
                throw new \Exception('Could not set API URL to sandbox API URL');
            }
        }
        if ($pw = $this->getUserOption('apiKeyPassword')) {
            if (!$this->setCCXTProperty('password', $pw)) {
                throw new \Exception('Could not set API Key Password');
            }
        }
    }


    public function getSandboxApiUrl()
    {
        return $this->getCCXTProperty(['urls', 'test']) ?? null;
    }


    public function handleSaveRequest(Request $request, UserExchangeConfig $config)
    {
        $r_options = $request->options ?? [];
        $c_options = $config->options ?? [];
        if ($url = $this->getSandboxApiUrl()) {
            foreach (['use_sandbox'] as $param) {
                if (isset($r_options[$param])) {
                    $c_options[$param] = $r_options[$param] ? 1 : 0;
                }
            }
        }
        foreach (['apiKeyPassword'] as $param) {
            if (isset($r_options[$param])) {
                $c_options[$param] = $r_options[$param];
            }
        }
        $config->options = $c_options;
        return parent::handleSaveRequest($request, $config);
    }
}
