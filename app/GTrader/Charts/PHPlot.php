<?php

namespace GTrader\Charts;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use GTrader\Chart;
use GTrader\Exchange;
use GTrader\Page;
//use PHPlot_truecolor;
use GTrader\Util;
use GTrader\Log;

class PHPlot extends Chart
{
    protected $data = [];
    protected $last_close;
    protected $image_map;

    protected $colors = [];
    protected $label = [];
    protected $debug = [];


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
        $candles = $this->getCandles();
        //$this->setParam('width', $this->getParam('width') + 200);
        if (!$this->initPlot()) {
            Log::error('Could not init plot');
            return '';
        }
        $this->setParam('density_cutoff', $this->getParam('width'));
        $this->setParam('image_map_active', false);

        if (!$this->createDataArray()) {
            Log::error('Could not create data array');
            return '';
        }
        //dd($this->data);
        $this->setTitle()
            ->setColors()
            ->initPlotElements();

        $t = Arr::get($this->data, 'times', [0]);
        if (!$resolution = $candles->getParam('resolution')) {
            Log::error('could not get resolution from candles');
            return '';
        }
        if (!$tfirst = $t[0]) {
            Log::error('could not get first time');
            return '';
        }
        if (!$tlast = $t[count($t)-1]) {
            Log::error('could not get last time');
            return '';
        }

        // Plot items on left Y-axis
        $this->setParam('xmin', $tfirst - $resolution);
        $this->setParam('xmax', $tlast + $resolution);
        $this->setWorld([
            'xmin' => $this->getParam('xmin'),
            'xmax' => $this->getParam('xmax'),
            'ymin' => $ymin = Arr::get($this->data, 'left.min', 0),
            'ymax' => $ymax = Arr::get($this->data, 'left.max', 0),
        ]);
        //dd($ymax, $ymin);
        $range = $ymax - $ymin;
        $this->setParam('precision', 3 < $range ? (10 < $range ? 0 : 1) : 2);
        foreach (Arr::get($this->data, 'left.items', []) as $index => $item) {
            $this->setPlotElements('left', $index, $item);
            $this->setYAxis($item, 'left');
            $this->plot($item);
        }

        // Plot items on right Y-axis
        foreach (Arr::get($this->data, 'right.items', []) as $index => $item) {
            $this->setPlotElements('right', $index, $item);
            $this->setYAxis($item, 'right');
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

        //dump($this->debug);
        // Map
        list($map, $map_str) = $this->getImageMapStrings();

        //Log::info('Memory used: '.Util::getMemoryUsage());
        //return $map.'<img class="img-responsive" src="'.
        return $map.'<img class="PHPlot-img" src="'.
                $this->PHPlot->EncodeImage().'"'.$map_str.'>'.
                $refresh;
    }


    public function createImageMap(array &$item)
    {
        if ($this->image_map ||
            in_array('map', $this->getParam('disabled', [])) ||
            !$this->getParam('image_map_active') ||
            'Ohlc' !== $item['class']) {
            return $this;
        }
        if ($this->getParam('width') < count($item['values'])) {
            $item['label'] .= ' imagemap: too many data points';
            return $this;
        }
        $times = Arr::get($this->data, 'times', [0]);
        $image_map =& $this->image_map;
        $radius = $this->getParam('width', 100) / count($item['values']);
        $radius = 5 <= $radius ? floor($radius) : 5;
        $this->PHPlot->SetCallback(
            'data_points',
            function (
                $im,
                $junk,
                $shape,
                $row,
                $col,
                $x1,
                $y1,
                $x2 = null,
                $y2 = null
            ) use (
                &$image_map,
                $times,
                $item,
                $radius
            ) {
                //dd($row);
                $title = 'T: '.date('Y-m-d H:i T', $times[$row]);
                $title .= ('rect' == $shape) ?
                    "\nO: ".$item['values'][$row][2]
                    ."\nH: ".$item['values'][$row][3]
                    ."\nL: ".$item['values'][$row][4]
                    ."\nC: ".$item['values'][$row][5] :
                    "\nO: ".$item['values'][$row][2];
                if ('rect' == $shape) {
                    $coords = sprintf('%d,%d,%d,%d', $x1, $y1, $x2, $y2);
                } else {
                    $coords = sprintf('%d,%d,%d', $x1, $y1, $radius);
                    $shape = 'circle';
                }
                $href = '#';
                $on_click = 'onClick="return window.GTrader.viewSample(\''.$this->getParam('name').'\''.
                    ', '.$times[$row].')"';
                $image_map .= '<area shape="'.$shape.'" coords="'.$coords.'" title="'.
                    $title.'" data-toggle="modal" data-target=".bs-modal-lg" '.$on_click.' href="'.$href.'">';
            }
        );
        return $this;
    }

    public function plot(array $item)
    {
        //Log::debug('plot ', $item);
        if (!is_array($item['values'])) {
            return $this;
        }
        // add an empty string and the timestamp to the beginning of each data array
        // and check if there are any non-empty values
        $t = Arr::get($this->data, 'times', []);
        $empty = 'imagemap' !== $item['mode'];
        array_walk($item['values'], function (&$v, $k) use ($t, &$empty) {
            if (!$time = Arr::get($t, $k)) {
                Log::error('Time not found for index '.$k);
            }
            if ($empty) {
                array_walk($v, function ($v, $k) use (&$empty) {
                    $empty = $empty ? empty($v) : false;
                });
            }
            array_unshift($v, '', $time);
        });
        if ($empty) {
            //dump('empty');
            return $this;
        }
        $this->setMode($item);
        $this->setHighlight($item);

        if (!is_array($item['values'])) {
            return $this;
        }
        if (!count($item['values'])) {
            return $this;
        }

        //dump($item, $this->colors);
        $this->PHPlot->setDataColors($this->colors);

        $this->PHPlot->SetDataValues($item['values']);
        //dump('before drawGraph()'.$item['label']);
        $this->PHPlot->drawGraph();
        //dump('after drawGraph()'.$item['label']);
        return $this;
    }


    protected function setYAxis(array $item, string $dir = 'left')
    {
        static $left_labels_shown = false;

        if (0 >= count(Arr::get($item, 'values', []))) {
            return $this;
        }

        if ('left' === $dir) {
            if ($left_labels_shown) {
                $this->PHPlot->SetDrawYGrid(false);
                $this->PHPlot->SetDrawXDataLabels(false);
                $this->PHPlot->SetDrawYAxis(false);
                $this->PHPlot->SetYTickLabelPos('none');
                return $this;
            }
            $left_labels_shown = true;
            //$this->PHPlot->SetDrawYAxis(true);
            //$this->PHPlot->SetYAxisPosition(200);
            $this->PHPlot->SetYTickLabelPos('plotright');
            $ohlc = 'Ohlc' === $item['class'];
            $this->PHPlot->SetDrawXGrid($ohlc);          // X grid lines
            $this->PHPlot->SetDrawYGrid($ohlc);          // Y grid lines
            $this->PHPlot->SetXTickLabelPos('plotdown'); // X tick labels
            return $this;
        }

        $this->PHPlot->SetYTickLabelPos('plotright');
        return $this;
    }



    protected function setMode(array &$item = [])
    {
        //dump('setMode', $item);
        $item['num_outputs'] = count(reset($item['values'])) - 2;

        // Line, linepoints and candlesticks use 'data-data'
        $this->PHPlot->SetDataType('data-data');

        // Set bar and candlesticks to line if it's too dense
        if (in_array($item['mode'], ['candlestick', 'bars'])) {
            $item['num_outputs'] = 2;
            $num_candles = ($n = $this->getCandles()->size(true)) ? $n : 10;
            if (2 > $this->getParam('width', 1) / $num_candles) {
                $item['num_outputs'] = 1;
                $item['mode'] = 'linepoints';
                // remove all but the first 3 data elements
                $item['values'] = array_map(function ($v) {
                    return [$v[0], $v[1], $v[2]];
                }, $item['values']);
            }
        }

        if ($set_mode = $this->map($item['mode'])) {
            if (in_array($set_mode, $this->getParam('plot_types'))) {
                $this->PHPlot->setPlotType($set_mode);
            }
        }

        $this->PHPlot->SetLineWidths(2);
        $this->colors = [];
        $this->PHPlot->setPointShapes('none');

        switch ($item['mode']) {
            case 'candlestick':
                $this->createImageMap($item);
                $this->mode_candlestick($item);
                break;
            case 'ohlc':
                $this->createImageMap($item);
                $this->mode_ohlc($item);
                break;
            case 'linepoints':
                $this->createImageMap($item);
                $this->mode_linepoints($item);
                break;
            case 'bars':
                $this->mode_bars($item);
                break;
            case 'imagemap':
                $this->mode_imagemap($item);
                break;
            case 'annotation':
                $this->mode_annotation($item);
                break;
            default: // line
                $this->mode_line($item);
        }

        if (0 >= $item['num_outputs']) {
            return $this;
        }
        $this->label = array_merge(
            [$item['label']],
            array_fill(0, $item['num_outputs'] - 1, '')
        );
        $this->PHPlot->SetLegend($this->label);
        $this->PHPlot->SetLegendPixels(5, self::nextLegendY($item['num_outputs']));

        return $this;
    }


    // Candles
    protected function mode_candlestick(array $item)
    {
        //dump('candles:', $item);
        $this->colors = ['#b0100010', '#00600010', 'grey:90', 'grey:90'];
        $this->PHPlot->SetLineWidths(1);

        /*
        PHPlot calculates a value to use for one half the width of the candlestick bodies, or
        for the OHLC open/close tick mark lengths, as follows:

        half_width = max(ohlc_min_width, min(ohlc_max_width, ohlc_frac_width * avail_area))
        Where avail_area = plot_area_width / number_data_points
        */

        // This is one half the maximum width of the candlestick body, or the maximum length of an
        // OHLC tick mark. The default is 8 pixels.
        $this->PHPlot->ohlc_max_width = 30;
        // This is one half the minimum width of the candlestick body, or the minimum length of an
        // OHLC tick mark. The default is 2 pixels.
        $this->PHPlot->ohlc_min_width = 1;
        // This is the fractional amount of the available space (plot width area divided by number
        // of points) to use for half the width of the candlestick bodies or OHLC tick marks. The
        // default is 0.3. This needs to be less than 0.5 or there will be overlap between adjacent candlesticks.
        $this->PHPlot->ohlc_frac_width = .3;

        return $this;
    }


    // OHLC
    protected function mode_ohlc(array &$item)
    {
        //dump('candles:', $item);
        //$this->PHPlot->SetDataType('text-data');
        $this->colors = ['#b0100010', '#00600010'];
        $item['num_outputs'] = 2;
        $this->PHPlot->SetLineWidths(2);
        return $this;
    }


    // Signals, Ohlc or Vol
    protected function mode_linepoints(array &$item)
    {
        //dump($item);
        if (strstr($item['class'], 'Signals')) {
            $item['num_outputs'] = 2;
            // 0 => red, 1 => green, 2 => blue, 3 => transparent
            $this->colors = ['#ff000010', '#00ff0050', '#0d00ff50', '#00000000'];
            $this->PHPlot->SetYDataLabelType('data', 2);         // precision
            $this->label = array_merge($this->label, ['']);
            $signals = $prev_signals = $values = [];
            $last_signal = null;
            foreach ($item['values'] as $k => $v) {
                if (isset($v[2])) {
                    $signals[] = $v[2];
                    $prev_signals[] = $last_signal;
                    $values[] = ['', $v[1], round($v[3], 2)];
                    $last_signal = $v[2];
                }
            }
            $item['values'] = $values;
            $this->PHPlot->SetCallback(
                'data_color',
                function ($img, $junk, $row, $col, $extra = 0) use (
                    $signals,
                    $prev_signals
                ) {
                    // $extra: 0 = line segment, 1 = marker shape
                    //Log::debug('R: '.$row.' C: '.$col.' E:'.$extra);
                    $s = $signals[$row] ?? null;
                    $ps = $prev_signals[$row] ?? null;
                    if (0 === $extra) { // line color
                        if ('short' === $ps) {
                            return 0;       // red
                        }
                        if ('long' === $ps) {
                            return 1;       // green
                        }
                        return 3;           // transparent
                    }
                    // marker color
                    if ('short' === $s) {
                        return 0;       // red
                    }
                    if ('long' === $s) {
                        return 1;       // green
                    }
                    return 2;           // blue
                }
            );
            //dd($item);
            $this->PHPlot->SetPointShapes('target');
            $this->PHPlot->SetLineStyles(['dashed']);
            $pointsize = floor($this->getParam('width', 1024) / 100);
            if (5 > $pointsize) {
                $pointsize = 5;
            }
            $this->PHPlot->SetPointSizes($pointsize);
            $this->PHPlot->SetYDataLabelPos('plotin');
            return $this;
        } else {
            $item['num_outputs'] = 1;
        }
        $this->colors = [self::nextColor()];
        $this->PHPlot->SetPointShapes('dot');
        $this->PHPlot->SetPointSizes(1);
        return $this;
    }


    // Patterns
    protected function mode_annotation(array &$item)
    {
        if (!function_exists('imagettftext')) {
            Log::error('Function imagettftext is missing, is PHP compiled with '.
                'freetype support (--with-freetype-dir=DIR)?');
            return $this;
        }

        //dump($item);
        $this->colors = ['#ff0000a3', '#00ff00b3'];
        $item['num_outputs'] = 2;
        $this->PHPlot->setPlotType('points');
        $this->PHPlot->setPointSizes(0);
        $this->PHPlot->SetPointShapes('dot');

        $contents = [];

        $t = Arr::get($this->data, 'times', []);
        //$item['values'] = [];

        foreach ($item['values'] as $key => $val) {
            $dot = null;
            if (isset($val[2]['price']) && isset($val[2]['contents'])) {
                $dot = $val[2]['price'];
                $contents[$t[$key]] = $val[2];
            }
            $item['values'][$key][2] = $dot;
        }
        $font_size = floor($this->getParam('width') / count($item['values']) / 2.5);
        $font_size = 5 > $font_size ? 5 : $font_size;
        $font_size = 16 < $font_size ? 16 : $font_size;
        $this->PHPlot->SetCallback('draw_all', function ($img, $plot) use ($contents, $font_size) {
            $red = imagecolorallocatealpha($img, 200, 0, 0, 66);
            $green = imagecolorallocatealpha($img, 0, 180, 0, 85);
            $font_path = storage_path('fonts/Vera.ttf');
            $rotation = 270; // counter-clockwise rotation

            foreach ($contents as $index => $content) {
                list($x, $y) = $plot->GetDeviceXY($index, $content['price']);
                //dump($x.' '.$y);
                $long = [];
                $short = [];
                array_walk($content['contents'], function ($v, $k) use (&$long, &$short) {
                    if (0 <= $v) {
                        $long[] = $k.' +'.$v;
                        return;
                    }
                    $short[] = $k.' '.$v;
                });
                if (count($long)) {
                    $text_long = join(', ', $long);
                    $text_coords = imagettfbbox($font_size, $rotation, $font_path, $text_long);
                    $xlong = $x - floor(($text_coords[6] - $text_coords[0]) / 3);
                    $ylong = $y - $text_coords[3] - 10;
                    imagettftext($img, $font_size, $rotation, $xlong, $ylong, $green, $font_path, $text_long);
                }
                if (count($short)) {
                    $text_short = join(', ', $short);
                    $text_coords = imagettfbbox($font_size, $rotation, $font_path, $text_short);
                    $xshort = $x - floor(($text_coords[6] - $text_coords[0]) / 3);
                    imagettftext($img, $font_size, $rotation, $xshort, $y + 10, $red, $font_path, $text_short);
                }
            }
        }, $this->PHPlot);

        return $this;
    }


    // Volume
    protected function mode_bars(array &$item)
    {
        //dump($item['values']);
        $this->colors = ['#ff0000f2', '#00ff00f2'];
        $this->PHPlot->SetTickLabelColor($this->colors[1]);

        $this->PHPlot->SetXTickLabelPos('none');

        $this->PHPlot->SetDataType('text-data');

        // Controls the amount of extra space within each group of bars.
        // Default is 0.5, meaning 1/2 of the width of one bar is left as a
        // gap, within the space allocated to the group (see group_frac_width).
        // Increasing this makes each group of bars shrink together. Decreasing
        // this makes the group of bars expand within the allocated space.
        $this->PHPlot->bar_extra_space = .1;

        //Controls the amount of available space used by each bar group. Default is
        // 0.7, meaning the group of bars fills 70% of the available space (but that
        // includes the empty space due to bar_extra_space). Increasing this makes the
        // group of bars wider.
        $this->PHPlot->group_frac_width = 1;

        // Controls the width of each bar. Default is 1.0. Decreasing this makes individual
        // bars narrower, leaving gaps between the bars in a group. This must be greater
        // than 0. If it is greater than 1, the bars will overlap.
        $this->PHPlot->bar_width_adjust = 1;

        // If bar_extra_space=0, group_frac_width=1, and bar_width_adjust=1 then
        // all the bars touch (within each group, and adjacent groups).

        // 3D look
        $this->PHPlot->SetShading('none');

        // convert ['', time, value...] to [time, value]
        $item['values'] = array_map(function ($v) {
            return [$v[1], $v[2]];
        }, $item['values']);

        $this->setWorld([
            'xmin' => -0.5,
            'xmax' => count($item['values'])+0.5,
            'ymin' => 0,
            'ymax' => $item['max'] * 2,
        ]);
        $this->PHPlot->SetXAxisPosition(0);

        // get rising/falling data sub(close, open)
        if ($sub = $this->getOrAddIndicator('Operator', [
            'input_a' => 'close',
            'operation' => 'sub',
            'input_b' => 'open',
        ])) {
            if ($sub = $sub->getOutputArray(
                'sequential',
                true,
                $this->getParam('density_cutoff')
            )) {
                $this->PHPlot->SetCallback(
                    'data_color',
                    function ($img, $junk, $row, $col, $extra = 0) use ($sub) {
                        $rising = isset($sub[$row][0]) ? (0 <= $sub[$row][0]) : 0;
                        //dump('R: '.$row.' C: '.$col.' E:'.$extra.' R:'.$rising.' R:', $sub[$row]);
                        return $rising ? 1 : 0;
                    }
                );
            }
        }
        return $this;
    }


    // Line
    protected function mode_line(array &$item)
    {
        //dump('default:', $item);
        for ($i = 0; $i < $item['num_outputs']; $i++) {
            $this->colors[] = $last_color = self::nextColor();
        }
        $this->PHPlot->SetTickLabelColor($last_color);

        return $this;
    }


    protected function mode_imagemap($item)
    {
        //dump('mode_imagemap', $item);
        $this->setParam('image_map_active', true);
    }



    protected function setHighlight(array $item)
    {
        if ('Ohlc' !== $item['class']) {
            return $this;
        }
        $highlight = $this->getParam('highlight', []);
        if (!count($highlight)) {
            return $this;
        }
        $times = Arr::get($this->data, 'times', [0]);

        $highlight_colors = ['yellow', 'red', 'blue', 'orange', 'pink'];
        $this->colors = array_merge($this->colors, $highlight_colors);
        $highlight_color_count = count($highlight_colors);

        $debug = &$this->debug;
        $this->PHPlot->SetCallback(
            'data_color',
            function (
                $img,
                $junk,
                $row,
                $col,
                $extra = 0
            ) use (
                $highlight,
                $item,
                $times,
                $highlight_color_count,
                &$debug
            ) {
                $return = ('candlestick' === $item['mode']) ? 1 : 0;
                $high_index = 0;
                foreach ($highlight as $range) {
                    if (($times[$row] >= $range['start']) && ($times[$row] <= $range['end'])) {
                        $return = (('candlestick' === $item['mode']) ? 4 : 1) + $high_index;
                        $high_index ++;
                    }
                    if ($high_index++ > $highlight_color_count) {
                        $high_index = 0;
                    }
                }
                $debug['highlight'][$row] = $return;
                return $return;
            }
        );
    }



    protected function createDataArray()
    {
        $candles = $this->getCandles();
        if (!$candles->size(true)) {
            Log::error('no candles');
            return $this;
        }

        if (!count($times = $candles->extract('time', 'sequential', true, $this->getParam('density_cutoff')))) {
            Log::error('Could not extract times');
            return false;
        }

        $this->data = [
            'times' => $times,
            'left' => [
                'items' => [],
            ],
            'right' => [
                'items' => [],
            ],
        ];

        $inds = $this->getIndicatorsVisibleSorted();

        while ($ind = array_shift($inds)) {
            $ind->checkAndRun();

            if ('Patterns' === $ind->getShortClass()) {
                if ('line' === $ind->getParam('display.mode')) {
                    if ($ind->getParam('indicator.show_annotation')) {
                        $ind->setParam('display.mode', 'annotation');
                        $ind->setParam('display.y-axis', 'left');
                        array_unshift($inds, $ind);
                    }
                } else {
                    $ind->setParam('display.mode', 'line');
                    $ind->setParam('display.y-axis', 'right');
                }
            }

            $dir = in_array(
                $dir = $ind->getParam('display.y-axis', 'left'),
                ['left', 'right']
            ) ? $dir : 'left';

            $mode = $ind->getParam('display.mode');
            $values = [];
            if ('imagemap' !== $mode) {
                $values = $ind->getOutputArray(
                    'sequential',
                    true,
                    $this->getParam('density_cutoff')
                );
            }

            $item = [
                'class' => $ind->getShortClass(),
                'label' =>
                    (700 < $this->getParam('width')) &&
                    ($this->getParam('labels.format') != 'short') ?
                        $ind->getDisplaySignature() :
                        $ind->getDisplaySignature('short'),
                'mode' => $mode,
                'values' => $values,
            ];

            if ('annotation' !== $mode) {
                // find min and max
                $values_min = $ind->min($item['values']);
                $values_max = $ind->max($item['values']);

                // Left Y-axis needs to know 'global' min and max
                if ('left' === $dir) {
                    $this->data['left']['min'] = $this->min(Arr::get($this->data, 'left.min'), $values_min);
                    $this->data['left']['max'] = $this->max(Arr::get($this->data, 'left.max'), $values_max);
                } else { // Right Y-axis items need individual min and max
                    $item['min'] = $values_min;
                    $item['max'] = $values_max;
                }
            }

            // used later to set the page title
            if ('Ohlc' === $item['class']) {
                $index = ('linepoints' === $item['mode']) ? 0 : 3;
                $this->last_close = $item['values'][count($item['values'])-1][$index];
            }

            $this->data[$dir]['items'][] = $item;
        }
        //dd($this->data);
        return $this;
    }


    protected function getRefreshString()
    {
        $refresh = null;
        if (!$this->getParam('autorefresh') ||
            !($refresh = $this->getParam('refresh'))) {
            return null;
        }
        if ($this->getCandles()->getParam('end')) {
            return null;
        }
        $refresh = "<script>window.GTrader.waitForFinalEvent(function () {window.GTrader.charts.".
            $this->getParam('name').".refresh()}, ".
            ($refresh * 1000).", 'refresh_".
            $this->getParam('name')."');";
        if ($this->last_close) {
            $refresh .= "document.title = '".number_format($this->last_close, 2).' - '.
                config('app.name', 'GTrader')."';";
        }
        $refresh .= '</script>';

        return $refresh;
    }


    protected function getImageMapStrings()
    {
        if (in_array('map', $this->getParam('disabled', [])) ||
            !$this->image_map) {
            return ['', ''];
        }
        $map_name = 'map-'.$this->getParam('name');
        $map = '<map name="'.$map_name.'">'.$this->image_map.'</map>';
        $map_str = ' usemap="#'.$map_name.'"';
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
        $this->PHPlot->setTitle($title);
        return $this;
    }


    protected function setPlotElements(string $dir = 'left', int $index = null, array $item)
    {
        if (0 >= count(Arr::get($item, 'values', []))) {
            return $this;
        }

        if ('left' === $dir) {
        } else {
            $this->PHPlot->SetDrawXGrid(false);
            $this->PHPlot->SetDrawYGrid(false);
            $this->PHPlot->SetXTickLabelPos('none');
        }


        $this->PHPlot->RemoveCallback('draw_all');
        $this->PHPlot->RemoveCallback('data_color');
        $this->PHPlot->RemoveCallback('data_points');

        $this->PHPlot->SetPlotBorderType('none');    // plot area border

        $this->PHPlot->SetDrawXAxis(false);          // X axis line
        $this->PHPlot->SetXTickPos('none');          // X tick marks
        $this->PHPlot->SetXDataLabelPos('none');     // X data labels

        $this->PHPlot->SetDrawYAxis(false);          // Y axis line

        $this->PHPlot->SetYTickLabelPos('none');
        $this->PHPlot->SetYDataLabelPos('none');

        $this->PHPlot->SetLineStyles(['solid']);
        $this->PHPlot->SetTickLabelColor('#555555');

        $this->PHPlot->SetYLabelType('data', $this->getParam('precision', 0)); // precision
        $this->PHPlot->SetMarginsPixels(30, 30, 15);
        $this->PHPlot->SetLegendStyle('left', 'left');
        //$this->PHPlot->SetLegendUseShapes(true);
        $this->PHPlot->SetLegendColorboxBorders('none');

        return $this;
    }



    protected function initPlotElements()
    {
        $this->getCandles()->reset(true);
        $start = ($first = $this->getCandles()->next()) ? $first->time : 0;
        $end = ($last = $this->getCandles()->last()) ? $last->time : 0;
        $longtime = 3600*24 < ($end - $start);
        $this->PHPlot->SetNumXTicks(floor($this->getParam('width', 200) / ($longtime ? 70 : 40)));
        $this->PHPlot->SetXLabelType('time', $longtime ? '%m-%d %H:%M' : '%H:%M');
        $this->PHPlot->SetYTickPos('none');

        return $this;
    }



    protected function setColors()
    {
        $this->PHPlot->SetBackgroundColor('black');
        $this->PHPlot->SetLegendBgColor('black:50');
        $this->PHPlot->SetGridColor('DarkGreen:100');
        $this->PHPlot->SetLightGridColor('DimGrey:110');
        $this->PHPlot->setTitleColor('DimGrey:80');
        $this->PHPlot->SetTickColor('DarkGreen');
        $this->PHPlot->SetTextColor('#999999');
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
        $this->setParam('width', $request->width ?? 0);
        $this->setParam('height', $request->height ?? 0);
        $candles = $this->getCandles();
        foreach (['start', 'end', 'limit', 'exchange', 'symbol', 'resolution'] as $param) {
            if (isset($request->$param)) {
                $candles->setParam($param, $request->$param);
            }
        }
        if (isset($request->command)) {
            $args = is_null($args = json_decode($request->args, true)) ? [] : $args;
            $this->handleCommand($request->command, $args);
        }
        return $this;
    }




    protected function handleCommand(string $command, array $args = [])
    {
        //Log::debug('Command: '.$command.' args: ', $args);
        $candles = $this->getCandles();
        $end = $candles->getParam('end');
        $limit = $candles->getParam('limit');
        if (!$resolution = $candles->getParam('resolution')) {
            Log::error('could not get resolution');
            $resolution = 0;
            //return $this;
        }
        if (!$last = $candles->getLastInSeries()) {
            Log::error('Could not get last candle');
            $last = 0;
            //return $this;
        }
        $live = ($end == 0) || $end > $last - $resolution;
        if ($live) {
            $end = 0;
            // Set refresh to half exchange fetch frequency (which is in minutes)
            $this->setParam(
                'refresh',
                config('GTrader.Exchange.schedule_frequency', 1) * 30
            );
        }
        //Log::error('handleCommand live: '.$live.' end: '.$end.' limit: '.$limit);
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
