<?php

namespace GTrader\Strategies;

use GTrader\Series;
use GTrader\Exchange;
use GTrader\Chart;
use GTrader\Training;
use GTrader\Log;

trait Trainable
{
    public function getTrainingClass()
    {
        if (!$class = $this->getParam('training_class')) {
            Log::error('no training class', $this->getparam('id'));
            return null;
        }
        return 'GTrader\\Strategies\\'.$class;
    }

    /**
     * Display strategy as list item.
     * @return string
     */
    public function listItem()
    {
        $training_class = $this->getTrainingClass();
        if (!class_exists($training_class)) {
            return parent::listItem();
        }
        $class = $this->getShortClass();
        try {
            $training =
                $training_class::select('status')
                ->where('strategy_id', $this->getParam('id'))
                ->where(function ($query) {
                    $query->where('status', 'training')
                        ->orWhere('status', 'paused');
                })
                ->first();
            $training_status = null;
            if (is_object($training)) {
                $training_status = $training->status;
            }

            $html = view(
                'Strategies/'.$class.'ListItem',
                [
                    'strategy' => $this,
                    'training_status' => $training_status
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed id: '.$this->getParam('id'), $e->getMessage());
            $html = '[Failed to display '.$class.'ListItem] '.parent::listItem();
        }
        return $html;
    }


    /**
     * Training progress chart.
     * @param Training  $training
     * @return Chart
     */
    public function getTrainingProgressChart(Training $training)
    {
        $candles = new Series([
            'limit' => 0,
            'exchange' => Exchange::getNameById($training->exchange_id),
            'symbol' => Exchange::getSymbolNameById($training->symbol_id),
            'resolution' => $training->resolution,
        ]);

        $highlights = [];
        foreach (array_keys($training->getParam('ranges')) as $range) {
            if (isset($training->options[$range.'_start']) &&
                isset($training->options[$range.'_end'])) {
                $highlights[] = [
                    'start' => $training->options[$range.'_start'],
                    'end' => $training->options[$range.'_end']
                ];
            }
        }

        $chart = Chart::make(null, [
            'candles' => $candles,
            'strategy' => $this,
            'name' => 'trainingProgressChart',
            'height' => 200,
            'disabled' => ['title', 'strategy', 'map', 'settings', 'fullscreen'],
            'readonly' => ['esr'],
            'highlight' => $highlights,
            'visible_indicators' => ['Ohlc', 'Balance', 'Profitability'],
        ]);
        $ind = $chart->getOrAddIndicator('Ohlc', ['mode' => 'linepoints']);
        $ind->visible(true);
        $ind->addRef('root');

        $sig = $training->getMaximizeSig($this);
        $ind = $chart->getOrAddIndicator($sig);
        $ind->visible(true);
        $ind->addRef('root');

        if (!$ind = $chart->getFirstIndicatorByClass('Balance')) {
            $ind = $chart->getOrAddIndicator('Balance');
        }
        $ind->visible(true);
        $ind->addRef('root');

        $signal_sig = $this->getSignalsIndicator()->getSignature();
        if (!$chart->hasIndicatorClass('Profitability')) {
            $ind = $chart->getOrAddIndicator('Profitability', [
                'input_signal' => $signal_sig,
            ]);
            $ind->visible(true);
            $ind->addRef('root');
        }

        $chart->saveToSession();
        return $chart;
    }


    public function deleteTrainings()
    {
        if (!$class = $this->getTrainingClass()) {
            Log::error('no training class', $this->getparam('id'));
            return $this;
        }
        $class::where(
            'strategy_id',
            $this->getParam('id')
        )->delete();
        return $this;
    }
}
