<?php

namespace GTrader\Charts;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use GTrader\Chart;
use GTrader\Exchange;
use GTrader\Page;
//use PHPlot_truecolor;
use GTrader\Util;

class PHPlot extends Chart
{

    protected $data = [];
    protected $last_close;
    protected $image_map;

    protected $colors;
    protected $label;


    public function toHTML(string $content = '')
    {
        $content = view('Charts/PHPlot', [
            'name' => $this->getParam('name'),
            'disabled' => $this->getParam('disabled', [])
        ]);

        return parent::toHTML($content);
    }


    public function getImage()
    {
        // Init
        $candles = $this->getCandles();
        if (!$this->initPlot()) {
            error_log('PHPlot::getImage() could not init plot');
            return '';
        }
        $this->setParam('density_cutoff', $this->getParam('width'));

        if (!$this->createDataArray()) {
            error_log('PHPlot::getImage() could not create data array');
            return '';
        }

        $this->setTitle()
            ->setColors()
            ->setPlotElements();

        $t = Arr::get($this->data, 'times', [0]);

        // Plot items on left Y-axis
        $this->setParam('xmin', $t[0] - $candles->getParam('resolution'));
        $this->setParam('xmax', $t[count($t)-1] + $candles->getParam('resolution'));
        $this->setWorld([
            'xmin' => $this->getParam('xmin'),
            'xmax' => $this->getParam('xmax'),
            'ymin' => Arr::get($this->data, 'left.min', 0),
            'ymax' => Arr::get($this->data, 'left.max', 0),
        ]);
        foreach (Arr::get($this->data, 'left.items', []) as $item) {
            $this->setYAxis('left');
            $this->plot($item);
        }

        // Plot items on right Y-axis
        foreach (Arr::get($this->data, 'right.items', []) as $item) {
            $this->setYAxis('right');
            $this->setWorld([
                'xmin' => $this->getParam('xmin'),
                'xmax' => $this->getParam('xmax'),
                'ymin' => Arr::get($item, 'min', 0),
                'ymax' => Arr::get($item, 'max', 0),
            ]);
            $this->plot($item);
        }

        // Refresh
        $refresh = $this->getRefreshString();

        // Map
        list ($map, $map_str) = $this->getImageMapStrings();

        //error_log('PHPlot::getImage() memory used: '.Util::getMemoryUsage());
        return $map.'<img class="img-responsive" src="'.
                $this->_plot->EncodeImage().'"'.$map_str.'>'.$refresh;
    }


    public function plot(array $item)
    {

        // add an empty string and the timestamp to the beginning of each data array
        $t = Arr::get($this->data, 'times', []);
        array_walk($item['values'], function (&$v, $k) use ($t) {
            if (!$time = Arr::get($t, $k, 0)) {
                error_log('plot() time not found for index '.$k);
            }
            array_unshift($v, '', $time);
        });

        $this->setMode($item);
        $this->setHighlight($item);

        if (!count($item['values'])) {
            error_log('PHPlot::plot() no data values for '.$item['label']);
            return $this;
        }

        $this->_plot->SetDataValues($item['values']);

        $this->_plot->drawGraph();
        //dump($item);
        return $this;
    }



    protected function setYAxis(string $dir = 'left')
    {
        static $left_labels_shown = false;
        if ('left' === $dir) {
            if ($left_labels_shown) {
                $dir = 'none';
                $this->_plot->SetDrawYGrid(false);
                $this->_plot->SetDrawXDataLabels(false);
            }
            $left_labels_shown = true;
        }

        if (in_array($dir, ['left', 'right'])) {
            $dir = 'plot'.$dir;
        }
        $this->_plot->SetYTickPos('none');
        $this->_plot->SetYTickLabelPos($dir);
        return $this;
    }




    protected function setMode(array &$item = [])
    {
        $item['num_outputs'] = count(reset($item['values'])) - 2;

        // Line, linepoints and candlesticks use 'data-data'
        $this->_plot->SetDataType('data-data');

        // clear settings from prev items
        $this->setPlotElements();

        // Set bar and candlesticks to line if it's too dense
        if (in_array($item['mode'], ['candlestick', 'bars'])) {
            $item['num_outputs'] = 2;
            $num_candles = ($n = $this->getCandles()->size(true)) ? $n : 10;
            if (2 > $this->getParam('width', 1) / $num_candles) {
                //$item['num_outputs'] = 1;
                $item['mode'] = 'line';
                // remove all but the first 3 data elements
                $item['values'] = array_map(function ($v) {
                    return [$v[0], $v[1], $v[2]];
                }, $item['values']);
            }
        }

        $this->_plot->setPlotType($this->map($item['mode']));

        $this->_plot->SetLineWidths(2);
        $this->colors = [];
        $this->_plot->setPointShapes('none');
        $this->label = array_merge(
            [$item['label']],
            array_fill(0, $item['num_outputs'] - 1, '')
        );

        switch ($item['mode']) {
            case 'candlestick':
                $this->mode_candlestick($item);
                break;
            case 'linepoints':
                $this->mode_linepoints($item);
                break;
            case 'bars':
                $this->mode_bars($item);
                break;
            default:
                $this->mode_line($item);
        }

        $this->_plot->SetLegend($this->label);
        $this->_plot->SetLegendPixels(35, self::nextLegendY($item['num_outputs']));
        $this->_plot->setDataColors($this->colors);
        return $this;
    }


    // Candles
    protected function mode_candlestick(array &$item)
    {
        //dump('candles:', $item);
        $this->colors = ['#b0100010', '#00600010', 'grey:90', 'grey:90'];
        $this->_plot->SetLineWidths(1);

        /*
        PHPlot calculates a value to use for one half the width of the candlestick bodies, or
        for the OHLC open/close tick mark lengths, as follows:

        half_width = max(ohlc_min_width, min(ohlc_max_width, ohlc_frac_width * avail_area))
        Where avail_area = plot_area_width / number_data_points
        */

        // This is one half the maximum width of the candlestick body, or the maximum length of an
        // OHLC tick mark. The default is 8 pixels.
        $this->_plot->ohlc_max_width = 30;
        // This is one half the minimum width of the candlestick body, or the minimum length of an
        // OHLC tick mark. The default is 2 pixels.
        $this->_plot->ohlc_min_width = 1;
        // This is the fractional amount of the available space (plot width area divided by number
        // of points) to use for half the width of the candlestick bodies or OHLC tick marks. The
        // default is 0.3. This needs to be less than 0.5 or there will be overlap between adjacent candlesticks.
        $this->_plot->ohlc_frac_width = .3;

        return $this;
    }

    // Signals
    protected function mode_linepoints(array &$item)
    {
        $this->colors = ['#ff000010', '#00ff0050'];
        $this->label = array_merge($this->label, ['']);
        $signals = $values = [];
        foreach ($item['values'] as $k => $v) {
            if (isset($v[2]['signal'])) {
                $signals[] = $v[2]['signal'];
                $values[] = ['', $v[1], round($v[2]['price'], 2)];
            }
        }
        $item['values'] = $values;
        $this->_plot->SetCallback(
            'data_color',
            function ($img, $junk, $row, $col, $extra = 0) use ($signals) {
                //dump('R: '.$row.' C: '.$col.' E:'.$extra);
                $s = isset($signals[$row]) ? $signals[$row] : null;;
                if ('long' === $s) {
                    return (0 === $extra) ? 0 : 1;
                } elseif ('short' === $s) {
                    return (0 === $extra) ? 1 : 0;
                }
                error_log('Unmatched signal');
            }
        );
        //dd($item);
        $this->_plot->SetPointShapes('target');
        $this->_plot->SetLineStyles(['dashed']);
        $pointsize = floor($this->getParam('width', 1024) / 100);
        if (5 > $pointsize) {
            $pointsize = 5;
        }
        $this->_plot->SetPointSizes($pointsize);
        $this->_plot->SetYDataLabelPos('plotin');
        return $this;
    }


    // Volume
    protected function mode_bars(array &$item)
    {
        //dump($item);
        $this->colors = ['#ff0000f2', '#00ff00f2'];
        $this->_plot->SetTickLabelColor($this->colors[1]);

        $this->_plot->SetXTickLabelPos('none');


        $this->_plot->SetDataType('text-data');

        // Controls the amount of extra space within each group of bars.
        // Default is 0.5, meaning 1/2 of the width of one bar is left as a
        // gap, within the space allocated to the group (see group_frac_width).
        // Increasing this makes each group of bars shrink together. Decreasing
        // this makes the group of bars expand within the allocated space.
        $this->_plot->bar_extra_space = 0;

        //Controls the amount of available space used by each bar group. Default is
        // 0.7, meaning the group of bars fills 70% of the available space (but that
        // includes the empty space due to bar_extra_space). Increasing this makes the
        // group of bars wider.
        $this->_plot->group_frac_width = 1;

        // Controls the width of each bar. Default is 1.0. Decreasing this makes individual
        // bars narrower, leaving gaps between the bars in a group. This must be greater
        // than 0. If it is greater than 1, the bars will overlap.
        $this->_plot->bar_width_adjust = 1;

        // If bar_extra_space=0, group_frac_width=1, and bar_width_adjust=1 then
        // all the bars touch (within each group, and adjacent groups).


        // convert ['', time, value...] to [time, value]
        $item['values'] = array_map(function ($v) {
            return [$v[1], $v[2]];
        }, $item['values']);
        //dump($item);
        $this->setWorld([
            'xmin' => -0.5,
            'xmax' => count($item['values'])+0.5,
            'ymin' => 0,
            'ymax' => $item['max'] * 2,
        ]);
        // get rising/falling data from Roc(close)
        if ($roc = $this->getOrAddIndicator('Roc',
            ['indicator' => ['input_source' => 'close']]
        )) {
            if ($roc = $roc->getOutputArray('sequential', true,
                $this->getParam('density_cutoff')
            )) {
                $this->_plot->SetCallback(
                    'data_color',
                    function ($img, $junk, $row, $col, $extra = 0) use ($roc) {
                        $rising = isset($roc[$row][0]) ? (0 <= $roc[$row][0]) : 0;
                        //dump('R: '.$row.' C: '.$col.' E:'.$extra.' R:'.$rising.' R:', $roc[$row]);
                        return $rising ? 1 : 0;
                    }
                );
            }
        }
        $this->_plot->SetShading('none');
        return $this;
    }


    // Line
    protected function mode_line(array &$item)
    {
        //dump('default:', $item);
        for ($i = 0; $i < $item['num_outputs']; $i++) {
            $this->colors[] = $last_color = self::nextColor();
        }
        $this->_plot->SetTickLabelColor($last_color);

        return $this;
    }


    protected function setHighlight(array $item)
    {
        $highlight = $this->getParam('highlight', []);
        if (!count($highlight)) {
            return $this;
        }
        $times = Arr::get($this->data, 'times', [0]);

        $highlight_colors = ['yellow', 'red', 'blue', 'orange', 'pink'];
        $highlight_color_count = count($highlight_colors);

        $this->_plot->SetCallback(
            'data_color',
            function (
                $img,
                $junk,
                $row,
                $col,
                $extra = 0) use (
                $highlight,
                $item,
                $times,
                $highlight_color_count
            ) {
                $return = ('candlestick' === $item['mode']) ? 1 : 0;
                $high_index = 0;
                foreach ($highlight as $high_range) {
                    if (($times[$row] >= $high_range['start']) && ($times[$row] <= $high_range['end'])) {
                        $return = (('candlestick' === $item['mode']) ? 4 : 1) + $high_index;
                        $high_index ++;
                    }
                    $high_index ++;
                    if ($high_index > $highlight_color_count) {
                        $high_index = 0;
                    }
                }
                return $return;
            }
        );
    }



    protected function createDataArray()
    {
        $candles = $this->getCandles();
        if (!$candles->size(true)) {
            error_log('PHPlot::createDataArray() no candles');
            return $this;
        }

        if (!count($times = $candles->extract(
            'time',
            'sequential',
            true,
            $this->getParam('density_cutoff')
        ))) {
            error_log('PHPlot::createDataArray() could not extract times');
            return false;
        }

        $this->data = [
            'times' => $times,
            'left' => $items = [
                'items' => []
            ],
            'right' => $items,
        ];

        foreach ($this->getIndicatorsVisibleSorted() as $ind) {

            $ind->checkAndRun();

            $dir = in_array(
                $dir = $ind->getParam('display.y_axis_pos', 'left'),
                ['left', 'right']
            ) ? $dir : 'left';

            $sig = $ind->getSignature();
            $item = [
                'class' => $ind->getShortClass(),
                'label' => 380 < $this->getParam('width') ?
                    $ind->getDisplaySignature() :
                    $ind->getDisplaySignature('short'),
                'mode' => $ind->getParam('display.mode'),
                'values' => $ind->getOutputArray(
                    'sequential',
                    true,
                    $this->getParam('density_cutoff')
                ),
            ];

            // find min and max
            $values_min = $ind->min($item['values']);
            $values_min = is_null($values_min) ? null : $this->min(Arr::get($item, 'min'), $values_min);
            $values_max = $ind->max($item['values']);
            $values_max = is_null($values_max) ? null : $this->max(Arr::get($item, 'max'), $values_max);

            // Left Y-axis needs to know 'global' min and max
            if ('left' === $dir) {
                $this->data['left']['min'] = $this->min(Arr::get($this->data, 'left.min'), $values_min);
                $this->data['left']['max'] = $this->max(Arr::get($this->data, 'left.max'), $values_max);
            }

            // Right Y-axis items need individual min and max
            else {
                $item['min'] = $values_min;
                $item['max'] = $values_max;
            }

            // used later to set the page title
            if ('Ohlc' === $item['class']) {
                $this->last_close = $item['values'][count($item['values'])-1][0];
            }

            $this->data[$dir]['items'][] = $item;
        }
        //dd($this->data);
        return $this;
    }


    protected function getRefreshString()
    {
        $refresh = null;
        if ($this->getParam('autorefresh') &&
            ($refresh = $this->getParam('refresh'))) {
            $refresh = "<script>window.waitForFinalEvent(function () {window.".
                $this->getParam('name').".refresh()}, ".
                ($refresh * 1000).", 'refresh".
                $this->getParam('name')."');";
            if ($this->last_close) {
                $refresh .= "document.title = '".number_format($this->last_close, 2).' - '.
                                \Config::get('app.name', 'GTrader')."';";
            }
            $refresh .= '</script>';
        }
        return $refresh;
    }


    protected function getImageMapStrings()
    {
        $map = $map_str = '';
        if (!in_array('map', $this->getParam('disabled', []))) {
            $map_name = 'map-'.$this->getParam('name');
            $map = '<map name="'.$map_name.'">'.$this->image_map.'</map>';
            $map_str = ' usemap="#'.$map_name.'"';
        }
        return [$map, $map_str];
    }


    protected function setTitle()
    {
        $candles = $this->getCandles();
        $candles->reset();
        $first = ($c = $candles->next()) ? $c->time : null;
        $last = ($c = $candles->last()) ? $c->time : null;
        $title = in_array('title', $this->getParam('disabled', [])) ? '' :
            "\n".str_replace('_', '', $candles->getParam('exchange')).' '.
            strtoupper(str_replace('_', '', $candles->getParam('symbol'))).' '.
            $candles->getParam('resolution').' '.
            date('Y-m-d H:i', $first).' - '.
            date('Y-m-d H:i', $last);
        $this->_plot->setTitle($title);
        return $this;
    }


    protected function setPlotElements()
    {
        $this->_plot->RemoveCallback('data_color');

        $this->_plot->SetPlotBorderType('none');    // plot area border

        $this->_plot->SetDrawXAxis(false);          // X axis line
        $this->_plot->SetDrawXGrid(true);           // X grid lines
        $this->_plot->SetXTickPos('none');          // X tick marks
        $this->_plot->SetXTickLabelPos('plotdown'); // X tick labels
        $this->_plot->SetXDataLabelPos('none');     // X data labels

        $this->_plot->SetDrawYAxis(false);          // Y axis line
        $this->_plot->SetDrawYGrid(true);           // Y grid lines

        $this->_plot->SetYDataLabelPos('none');
        $this->_plot->SetLineStyles(['solid']);
        $this->_plot->SetTickLabelColor('#999999');

        $this->_plot->SetYLabelType('data', 0); // precision
        $this->_plot->SetMarginsPixels(30, 30, 15);
        $this->_plot->SetLegendStyle('left', 'left');
        //$this->_plot->SetLegendUseShapes(true);
        $this->_plot->SetLegendColorboxBorders('none');

        $start = ($first = $this->getCandles()->first()) ? $first->time : 0;
        $end = ($last = $this->getCandles()->last()) ? $last->time : 0;
        $longtime = 3600*24 < ($end - $start);
        $this->_plot->SetNumXTicks($xticks = floor($this->getParam('width', 200) / ($longtime ? 70 : 40)));
        $this->_plot->SetXLabelType('time', $longtime ? '%m-%d %H:%M' : '%H:%M');

        return $this;
    }


    protected function setColors()
    {
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetLegendBgColor('DimGrey:120');
        $this->_plot->SetGridColor('DarkGreen:100');
        $this->_plot->SetLightGridColor('DimGrey:110');
        $this->_plot->setTitleColor('DimGrey:80');
        $this->_plot->SetTickColor('DarkGreen');
        $this->_plot->SetTextColor('#999999');
        return $this;
    }


    public function addPageElements()
    {
        parent::addPageElements();

        Page::add('stylesheets', '<link href="'.mix('/css/PHPlot.css').'" rel="stylesheet">');
        Page::add('scripts_top', '<script src="/js/PHPlot.js"></script>');
        return $this;
    }



    public function handleImageRequest(Request $request)
    {
        $this->setParam('width', isset($request->width) ? $request->width : 0);
        $this->setParam('height', isset($request->height) ? $request->height : 0);
        $candles = $this->getCandles();
        foreach (['start', 'end', 'resolution', 'limit', 'exchange', 'symbol'] as $param) {
            if (isset($request->$param)) {
                $candles->setParam($param, $request->$param);
            }
        }
        if (isset($request->command)) {
            $this->handleCommand($request->command, (array)json_decode($request->args));
        }
        return $this->getImage();

    }




    private function handleCommand(string $command, array $args = [])
    {
        //error_log('Command: '.$command.' args: '.serialize($args));
        $candles = $this->getCandles();
        $end = $candles->getParam('end');
        $limit = $candles->getParam('limit');
        $resolution = $candles->getParam('resolution');
        $last = $candles->getLastInSeries();
        $live = ($end == 0) || $end > $last - $resolution;
        if ($live) {
            $end = 0;
            $this->setParam('refresh', 30); // seconds
        }
        //error_log('handleCommand live: '.$live.' end: '.$end.' limit: '.$limit);
        switch ($command) {
            case 'ESR':
                foreach (['exchange', 'symbol', 'resolution'] as $arg) {
                    if (isset($args[$arg])) {
                        $candles->setParam($arg, $args[$arg]);
                    }
                }
                break;

            case 'backward':
                if ($limit) {
                    $epoch = $candles->getEpoch();
                    if (!$end) {
                        $end = $last;
                    }
                    $end -= floor($limit * $resolution / 2);
                    if (($end - $limit * $resolution) < $epoch) {
                        $end = $epoch + $limit * $resolution;
                    }
                }
                break;

            case 'forward':
                if ($end) {
                    $end += floor($limit * $resolution / 2);
                    if ($end > $last) {
                        $end = 0;
                    }
                }
                break;

            case 'zoomIn':
                if (!$limit && $resolution) {
                    $epoch = $candles->getEpoch();
                    $limit = floor(($last - $epoch) / $resolution);
                }
                $limit = ceil($limit / 2);
                if ($limit < 10) {
                    $limit = 10;
                }
                break;

            case 'zoomOut':
                $epoch = $candles->getEpoch();
                $limit = $limit * 2;
                if ($limit > (($last - $epoch) / $resolution)) {
                    $limit = 0;
                }
                break;
        }
        $candles->setParam('limit', $limit);
        $candles->setParam('end', $end);
        $candles->cleanCache();
        return $this;
    }


    protected function min($a, $b)
    {
        return is_null($a) ? $b : (is_null($b) ? $a : min($a, $b));
    }


    protected function max($a, $b)
    {
        return is_null($a) ? $b : (is_null($b) ? $a : max($a, $b));
    }

    protected function map($key)
    {
        return $this->getParam('map.'.$key, $key);
    }



}
