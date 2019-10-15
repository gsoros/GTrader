<?php

namespace GTrader\Strategies;

use Illuminate\Support\Facades\DB;

use GTrader\Series;
use GTrader\Exchange;
use GTrader\Plot;
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
                $training_class::select('id', 'status')
                ->where('strategy_id', $this->getParam('id'))
                ->where(function ($query) {
                    $query->where('status', 'training')
                        ->orWhere('status', 'paused');
                })
                ->first();
            $training_id = $training_status = null;
            if (is_object($training)) {
                $training_id = $training->id;
                $training_status = $training->status;
            }

            $html = view(
                'Strategies/'.$class.'ListItem',
                [
                    'strategy' => $this,
                    'training_id' => $training_id,
                    'training_status' => $training_status,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed id: '.$this->getParam('id'), $e->getMessage());
            $html = '[Failed to display '.$class.'ListItem] '.parent::listItem();
        }
        return $html;
    }


    /**
     * Training chart for selecting the ranges.
     * @return Chart
     */
    public function getTrainingChart()
    {
        $exchange = Exchange::getDefault('exchange');
        $symbol = Exchange::getDefault('symbol');
        $resolution = Exchange::getDefault('resolution');
        $mainchart = session('mainchart');
        if (is_object($mainchart)) {
            $exchange = $mainchart->getCandles()->getParam('exchange');
            $symbol = $mainchart->getCandles()->getParam('symbol');
            $resolution = $mainchart->getCandles()->getParam('resolution');
        }
        $candles = new Series([
            'limit' => 0,
            'exchange' => $exchange,
            'symbol' => $symbol,
            'resolution' => $resolution,
        ]);
        $chart = Chart::make(null, [
            'candles' => $candles,
            'name' => 'trainingChart',
            'height' => 230,
            'disabled' => ['title', 'map', 'panZoom', 'strategy', 'settings'],
        ]);
        $ind = $chart->addIndicator('Ohlc', ['mode' => 'linepoints']);
        $ind->visible(true);
        $ind->addRef('root');
        $chart->saveToSession();
        return $chart;
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
            'heightPercentage' => 41,
            'disabled' => ['title', 'strategy', 'map', 'settings', 'fullscreen'],
            'readonly' => ['esr'],
            'highlight' => $highlights,
            'visible_indicators' => ['Ohlc', 'Balance', 'Profitability'],
            'labels' => ['format' => 'short'],
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


    public function delete()
    {
        $this->deleteTrainings();
        $this->deleteHistory();
        return parent::delete();
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


    /**
     * Delete training history.
     * @return $this
     */
    public function deleteHistory()
    {
        $affected = DB::table($this->getParam('history_table'))
            ->where('strategy_id', $this->getParam('id'))
            ->delete();
        Log::info($affected.' records deleted.');
        return $this;
    }


    /**
     * Save training history item.
     * @param  int    $epoch Training epoch
     * @param  string $name  Item name
     * @param  float  $value Item value
     * @return $this
     */
    public function saveHistory(int $epoch, string $name, float $value)
    {
        DB::table($this->getParam('history_table'))
            ->insert([
                'strategy_id' => $this->getParam('id'),
                'epoch' => $epoch,
                'name' => $name,
                'value' => $value,
            ]);
        return $this;
    }


    /**
     * Get number of history records.
     * @return int Number of records
     */
    public function getHistoryNumRecords()
    {
        return DB::table($this->getParam('history_table'))
            ->where('strategy_id', $this->getParam('id'))
            ->count();
    }


    /**
     * Returns a plot of the training history.
     * @param  int    $width    Plot width
     * @param  int    $height   Plot height
     * @return string
     */
    public function getHistoryPlot(int $width, int $height)
    {
        $data = [];
        $items = DB::table($this->getParam('history_table'))
            ->select('epoch', 'name', 'value')
            ->where('strategy_id', $this->getParam('id'))
            ->orderBy('epoch', 'desc')
            ->orderBy('name', 'desc')
            ->limit(15000)
            ->get()
            ->reverse()
            ->values();
        foreach ($items as $item) {
            $name = ucfirst(str_replace('_', ' ', $item->name));
            if (!array_key_exists($name, $data)) {
                $display = [];
                if ('train_mser' === $item->name) {
                    $display = ['y-axis' => 'right'];
                }
                $data[$name] = ['display' => $display, 'values' => []];
            }
            $data[$name]['values'][$item->epoch] = $item->value;
        }
        ksort($data);
        $plot = new Plot([
            'name' => 'History',
            'width' => $width,
            'height' => $height,
            'data' => $data,
        ]);
        return $plot->toHTML();
    }


    /**
     * Remove every nth training history record.
     * @param  integer $nth
     * @return $this
     */
    public function pruneHistory(int $nth = 2)
    {
        if ($nth < 2) {
            $nth = 2;
        }
        $epochs = DB::table($this->getParam('history_table'))
            ->select('epoch')
            ->distinct()
            ->where('strategy_id', $this->getParam('id'))
            ->get();
        $count = 1;
        $deleted = 0;
        foreach ($epochs as $epoch) {
            if ($count == $nth) {
                $deleted +=  DB::table($this->getParam('history_table'))
                    ->where('strategy_id', $this->getParam('id'))
                    ->where('epoch', $epoch->epoch)
                    ->delete();
            }
            $count ++;
            if ($count > $nth) {
                $count = 1;
            }
        }
        Log::info($deleted.' history records deleted.');
        return $this;
    }


    /**
     * @return int Epoch
     */
    public function getLastTrainingEpoch()
    {
        $res = DB::table($this->getParam('history_table'))
            ->select('epoch')
            ->where('strategy_id', $this->getParam('id'))
            ->orderBy('epoch', 'desc')
            ->limit(1)
            ->first();
        return is_object($res) ? intval($res->epoch) : 0;
    }

}
