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

    public function toHTML(string $content = null)
    {
        $content = view('Strategies/'.$this->getShortClass().'Form', [
            'strategy' => $this,
            'uid' => $this->getParam('uid'),
        ]);
        return parent::toHTML($content);
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
                'available' => $this->getIndicatorsAvailable(),
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
        return '<script>window.refresh_'.$this->getParam('uid').'();</script>';
    }

    public function getSourcesAvailable(
        string $except_signature = null,
        array $sources = [],
        array $filters = [],
        array $disabled = []
    ) {
        $filters = array_merge_recursive($filters, ['class' => ['not', 'Signals']]);
        return parent::getSourcesAvailable(
            $except_signature,
            $sources,
            $filters,
            $disabled
        );
    }

    public function getSignalsIndicator(array $options = [])
    {
        if ($ind = $this->cached('signals_indicator')) {
            return $ind;
        }
        if (!$ind = $this->getFirstIndicatorByClass('Signals')) {
            Log::error('Could not find Signals');
            return null;
        }
        $this->cache('signals_indicator', $ind);
        return $ind;
    }


    public function viewSignalForm()
    {
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not load signal Indicator');
            return null;
        }
        $signals->unsetParam('adjustable.strategy_id');
        $signals->setParam('uid', $this->getParam('uid'));
        return $signals->getForm(['disabled' => [
            'title' => true,
            'form' => true,
            'savebutton' => true,
            'save' => true,
        ]]);
    }

    protected function createDefaultIndicators()
    {

        $ohlc = $this->getOrAddIndicator('Ohlc');
        $ohlc_open = $ohlc->getSignature('open');

        $ema1 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length' => 20]);
        $ema2 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length' => 50]);

        $ema1_sig = $ema1->getSignature();
        $ema2_sig = $ema2->getSignature();

        $signals = Indicator::make(
            'Signals',
            [
                'indicator' => [
                    'strategy_id' => 0,                 // Custom Settings
                    'input_long_a' => $ema1_sig,
                    'long_cond' => '>=',
                    'input_long_b' => $ema2_sig,
                    'input_long_source' => $ohlc_open,
                    'input_short_a' => $ema1_sig,
                    'short_cond' => '<',
                    'input_short_b' => $ema2_sig,
                    'input_short_source' => $ohlc_open,
                ],
            ]
        );
        $signals->addAllowedOwner(get_class($this));
        $signals = $this->addIndicator($signals);
        $signals->addRef('root');

        $ohlc->visible(true);
        $ema1->visible(true);
        $ema2->visible(true);
        $signals->visible(true);

        return $this;
    }
}
