<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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


    public function viewIndicatorsList(Request $request = null, array $options = [])
    {
        $indicators_filters = array_replace_recursive(
            ['display.visible' => true, 'class' => ['not', 'Signals']],
            Arr::get($options, 'indicators.filters', [])
        );
        $indicators = $this->getIndicatorsFilteredSorted(
            $indicators_filters,
            Arr::get($options, 'indicators.sort', ['signature'])
        );
        $view_options = array_replace_recursive(
            [
                'owner' => $this,
                'indicators' => $indicators,
                'available' => $this->getAvailableIndicators(),
                'name' => 'strategy_'.$this->getParam('id'),
                'owner_class' => 'Strategy',
                'owner_id' => $this->getParam('id'),
                'display_outputs' => false,
                'disabled' => ['display'],
                'target_element' => 'strategy_indicators_list',
                'format' => 'long',
            ],
            Arr::get($options, 'view', [])
        );
        return view(
            'Indicators/List',
            $view_options
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
            //Log::debug('cached '.$sig);
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
                Log::error('Failed to create default');
                return null;
            }
        }
        $this->cache('signals_sig', $ind->getSignature());
        return $ind;
    }


    public function viewSignalsForm(array $options = [])
    {
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not load signals Indicator');
            return null;
        }
        $signals->unsetParam('adjustable.strategy_id');
        $signals->setParam('uid', $this->getParam('uid'));
        return $signals->getForm(array_replace_recursive(
            [
                'disabled' => [
                    'title', 'form', 'display', 'savebutton', 'save',
                ],
            ],
            Arr::get($options, 'view', [])
        ));
    }


    protected function createDefaultIndicators()
    {
        $ohlc       = $this->getOrAddIndicator('Ohlc');
        $ohlc_open  = $ohlc->getSignature('open');

        $ema9  = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 9]);
        $ema29 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 29]);
        $ema49 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 49]);
        $mid   = $this->getOrAddIndicator('Mid');

        $ema9_sig  = $ema9->getSignature();
        $ema29_sig = $ema29->getSignature();
        $ema49_sig = $ema49->getSignature();
        $mid_sig   = $mid->getSignature();

        $signals = Indicator::make(
            'Signals',
            [
                'indicator' => [
                    'strategy_id'               => 0,           // Custom Settings
                    'input_open_long_a'         => $ema9_sig,
                    'open_long_cond'            => '>',
                    'input_open_long_b'         => $ema49_sig,
                    'input_open_long_source'    => $mid_sig,
                    'input_close_long_a'        => $ema9_sig,
                    'close_long_cond'           => '<',
                    'input_close_long_b'        => $ema29_sig,
                    'input_close_long_source'   => $mid_sig,
                    'input_open_short_a'        => $ema9_sig,
                    'open_short_cond'           => '<',
                    'input_open_short_b'        => $ema49_sig,
                    'input_open_short_source'   => $mid_sig,
                    'input_close_short_a'       => $ema9_sig,
                    'close_short_cond'          => '>',
                    'input_close_short_b'       => $ema29_sig,
                    'input_close_short_source'  => $mid_sig,
                ],
            ]
        );
        $signals->addAllowedOwner($this);
        $signals = $this->addIndicator($signals);
        $signals->addRef('root');

        $ohlc->visible(true);
        $ema9->visible(true);
        $ema29->visible(true);
        $ema49->visible(true);
        $mid->visible(true);
        $signals->visible(true);

        return $this;
    }
}
