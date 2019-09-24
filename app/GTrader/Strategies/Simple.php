<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;
use GTrader\Strategy;
use GTrader\Indicator;
use GTrader\Log;

class Simple extends Strategy
{

    /**
     * Adds an initial indicator.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->createDefaultIndicators();
        $this->setParam('uid', uniqid());
    }


    public function __clone()
    {
        $this->setParam('uid', uniqid());
        parent::__clone();
    }


    public function toHTML(string $content = null)
    {
        return parent::toHTML(
            view(
                'Strategies/SimpleForm',
                [
                    'strategy' => $this,
                    'uid' => $this->getParam('uid'),
                    'injected' => $content,
                ]
            )
        );
    }


    public function viewIndicatorsList(Request $request = null)
    {
        $indicators = $this->getIndicatorsFilteredSorted(
            ['display.visible' => true, 'class' => ['not', 'Signals']],
            ['display.name']
        );
        return view(
            'Indicators/List',
            [
                'owner' => $this,
                'indicators' => $indicators,
                'available' => $this->getAvailableIndicators(),
                'name' => 'strategy_'.$this->getParam('id'),
                'owner_class' => 'Strategy',
                'owner_id' => $this->getParam('id'),
                'display_outputs' => false,
                'target_element' => 'strategy_indicators_list',
                'format' => 'long',
            ]
        );
    }


    public function handleSaveRequest(Request $request)
    {
        $reply = parent::handleSaveRequest($request);

        //dump($request->all());
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not get signals ind');
            return $reply;
        }
        $uid = $this->getParam('uid');
        $params = [];
        foreach ($signals->getParam('adjustable') as $key => $val) {
            $params[$key] = $request->{$key.'_'.$uid} ?? null;
        }
        $request->merge([
            'params' => json_encode($params),
            'signature' => urlencode($signals->getSignature()),
        ]);
        $this->handleIndicatorSaveRequest($request);
        return $reply;
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        return parent::handleIndicatorNewRequest($request).
            $this->getRefreshSignalsScript();
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        return parent::handleIndicatorSaveRequest($request).
            $this->getRefreshSignalsScript();
    }


    public function handleIndicatorDeleteRequest(Request $request)
    {
        return parent::handleIndicatorDeleteRequest($request).
            $this->getRefreshSignalsScript();
    }


    protected function getRefreshSignalsScript()
    {
        return '<script>window.refreshSignals_'.$this->getParam('uid').'();</script>';
    }


    public function getAvailableSources(
        $except_signatures = null,
        array $sources = [],
        array $filters = [],
        array $disabled = [],
        int $max_nesting = 0
    ):array {
        $filters = array_merge_recursive($filters, ['class' => ['not', 'Signals']]);
        return parent::getAvailableSources(
            $except_signatures,
            $sources,
            $filters,
            $disabled,
            $max_nesting
        );
    }


    public function getSignalsIndicator(array $options = [])
    {
        if ($sig = $this->cached('signals_sig')) {
            return $this->getOrAddIndicator($sig);
        }
        if (!$ind = $this->getFirstIndicatorByClass('Signals')) {
            //Log::error('Could not find Signals, creating default');
/*
            fwrite(
                $f = fopen('/gtrader/public/test.json', 'w'),
                json_encode(
                    $this->toVisArray(),
                    JSON_PRETTY_PRINT
                )
            );
            fclose($f);
            echo \GTrader\DevUtil::backtrace();
            dd('This shoud not have happened.');
 */
            $this->createDefaultIndicators();
            if (!$ind = $this->getFirstIndicatorByClass('Signals')) {
                //Log::error('Failed to create default');
                return null;
            }
        }
        $this->cache('signals_sig', $ind->getSignature());
        return $ind;
    }


    public function viewSignalsForm()
    {
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not load signals Indicator');
            return null;
        }
        $signals->unsetParam('adjustable.strategy_id');
        $signals->setParam('uid', $this->getParam('uid'));
        return $signals->getForm(['disabled' => [
            'title', 'form', 'savebutton', 'save'
        ]]);
    }


    protected function createDefaultIndicators()
    {

        $ohlc       = $this->getOrAddIndicator('Ohlc');
        $ohlc_open  = $ohlc->getSignature('open');
        $ohlc_close = $ohlc->getSignature('close');

        $ema1 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 15]);
        $ema2 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 55]);
        $mid  = $this->getOrAddIndicator('Mid');

        $ema1_sig = $ema1->getSignature();
        $ema2_sig = $ema2->getSignature();
        $mid_sig  = $mid->getSignature();

        $signals = Indicator::make(
            'Signals',
            [
                'indicator' => [
                    'strategy_id'        => 0,                 // Custom Settings
                    'input_long_a'       => $ema1_sig,
                    'long_cond'          => '>=',
                    'input_long_b'       => $ema2_sig,
                    'input_long_source'  => $mid_sig,
                    'input_short_a'      => $ema1_sig,
                    'short_cond'         => '<',
                    'input_short_b'      => $ema2_sig,
                    'input_short_source' => $mid_sig,
                ],
            ]
        );
        $signals->addAllowedOwner($this);
        $signals = $this->addIndicator($signals);
        $signals->addRef('root');

        $ohlc->visible(true);
        $ema1->visible(true);
        $ema2->visible(true);
        $mid->visible(true);
        $signals->visible(true);

        return $this;
    }
}
