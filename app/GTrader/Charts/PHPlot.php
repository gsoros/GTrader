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
    {   //dd($this->getIndicators());
        // Init
        $candles = $this->getCandles();
        //$this->setParam('width', $this->getParam('width') + 200);
        if (!$this->initPlot()) {
            error_log('PHPlot::getImage() could not init plot');
            return '';
        }
        $this->setParam('density_cutoff', $this->getParam('width'));

        if (!$this->createDataArray()) {
            error_log('PHPlot::getImage() could not create data array');
            return '';
        }
        //dd($this->data);
        $this->setTitle()
            ->setColors()
            ->initPlotElements();

        $t = Arr::get($this->data, 'times', [0]);

        // Plot items on left Y-axis
        $this->setParam('xmin', $t[0] - $candles->getParam('resolution'));
        $this->setParam('xmax', $t[count($t)-1] + $candles->getParam('resolution'));
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
            $this->setYAxis('left', $item);
            $this->plot($item);
        }

        // Plot items on right Y-axis
        foreach (Arr::get($this->data, 'right.items', []) as $index => $item) {
            $this->setPlotElements('right', $index, $item);
            $this->setYAxis('right', $item);
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
        list ($map, $map_str) = $this->getImageMapStrings();

        //error_log('PHPlot::getImage() memory used: '.Util::getMemoryUsage());
        //return $map.'<img class="img-responsive" src="'.
        return $map.'<img class="PHPlot-img" src="'.
                $this->_plot->EncodeImage().'"'.$map_str.'>'.
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
        $this->_plot->SetCallback(
            'data_points',
            function ($im, $junk, $shape, $row, $col, $x1, $y1, $x2 = null, $y2 = null) use
                (&$image_map, $times, $item, $radius) {
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
                $on_click = 'onClick="window.GTrader.viewSample(\''.$this->getParam('name').'\''.
                    ', '.$times[$row].')"';
                $image_map .= '<area shape="'.$shape.'" coords="'.$coords.'" title="'.
                    $title.'" data-toggle="modal" data-target=".bs-modal-lg" '.$on_click.' href="'.$href.'">';
            }
        );
        return $this;
    }

    public function plot(array $item)
    {
        //dump('plot '.$item['label']);
        if (!is_array($item['values'])) {
            return $this;
        }
        // add an empty string and the timestamp to the beginning of each data array
        // and check if there are any non-empty values
        $t = Arr::get($this->data, 'times', []);
        $empty = 'imagemap' !== $item['mode'];
        array_walk($item['values'], function (&$v, $k) use ($t, &$empty) {
            if (!$time = Arr::get($t, $k)) {
                error_log('plot() time not found for index '.$k);
            }
            if ($empty) {
                array_walk($v, function ($v, $k) use (&$empty){
                    $empty = $empty ? empty($v) : false;
                });
            }
            array_unshift($v, '', $time);
        });
        if ($empty) {
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
        $this->_plot->setDataColors($this->colors);

        $this->_plot->SetDataValues($item['values']);
        //dump('start '.$item['label']);
        $this->_plot->drawGraph();
        //dump('end plot '.$item['label']);
        return $this;
    }



    protected function setYAxis(string $dir = 'left', array $item)
    {
        static $left_labels_shown = false;

        if (0 >= count(Arr::get($item, 'values', []))) {
            return $this;
        }

        if ('left' === $dir) {
            if ($left_labels_shown) {
                $this->_plot->SetDrawYGrid(false);
                $this->_plot->SetDrawXDataLabels(false);
                $this->_plot->SetDrawYAxis(false);
                $this->_plot->SetYTickLabelPos('none');
                return $this;
            }
            $left_labels_shown = true;
            //$this->_plot->SetDrawYAxis(true);
            //$this->_plot->SetYAxisPosition(200);
            $this->_plot->SetYTickLabelPos('plotright');
            $ohlc = 'Ohlc' === $item['class'];
            $this->_plot->SetDrawXGrid($ohlc);          // X grid lines
            $this->_plot->SetDrawYGrid($ohlc);          // Y grid lines
            $this->_plot->SetXTickLabelPos('plotdown'); // X tick labels
            return $this;
        }

        $this->_plot->SetYTickLabelPos('plotright');
        return $this;
    }




    protected function setMode(array &$item = [])
    {
        $item['num_outputs'] = count(reset($item['values'])) - 2;

        // Line, linepoints and candlesticks use 'data-data'
        $this->_plot->SetDataType('data-data');

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
                $this->_plot->setPlotType($set_mode);
            }
        }

        $this->_plot->SetLineWidths(2);
        $this->colors = [];
        $this->_plot->setPointShapes('none');

        switch ($item['mode']) {
            case 'candlestick':
                $this->createImageMap($item);
                $this->mode_candlestick($item);
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
        $this->_plot->SetLegend($this->label);
        $this->_plot->SetLegendPixels(5, self::nextLegendY($item['num_outputs']));

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

    // Signals, Ohlc or Vol
    protected function mode_linepoints(array &$item)
    {
        if (stristr($item['class'], 'Signals')) {
            $this->colors = ['#ff000010', '#00ff0050'];
            $this->_plot->SetYDataLabelType('data', 2);         // precision
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
                    error_log('data_color callback: unknown signal '.$s);
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
        $this->colors = [self::nextColor()];
        $this->_plot->SetPointShapes('dot');
        $this->_plot->SetPointSizes(1);
        return $this;
    }


    // Patterns
    protected function mode_annotation(array &$item)
    {
        //dump($item);
        $this->colors = ['#ff0000a3', '#00ff00b3'];
        $item['num_outputs'] = 2;
        $this->_plot->setPlotType('points');
        $this->_plot->setPointSizes(0);
        $this->_plot->SetPointShapes('dot');

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
        $this->_plot->SetCallback('draw_all', function ($img, $plot)
            use ($contents, $font_size){

            $red = imagecolorallocatealpha($img, 200, 0, 0, 66);
            $green = imagecolorallocatealpha($img, 0, 180, 0, 85);
            $font_path = storage_path('fonts/Vera.ttf');
            $rotation = 270; // counter-clockwise rotation

            foreach ($contents as $index => $content) {
                list($x, $y) = $plot->GetDeviceXY($index, $content['price']);
                //dump($x.' '.$y);
                $long = [];
                $short = [];
                array_walk($content['contents'], function ($v, $k)
                    use (&$long, &$short) {
                    if (0 <= $v) {
                        $long[] = $k.' +'.$v;
                        return;
                    }
                    $short[] = $k.' '.$v;
                });
                if (count($long)) {
                    $text_long = join(', ', $long);
                    $text_coords = imagettfbbox($font_size, $rotation, $font_path, $text_long);
                    $ylong = $y - $text_coords[3] - 10;
                    imagettftext($img, $font_size, $rotation, $x-3, $ylong, $green, $font_path, $text_long);
                }
                if (count($short)) {
                    $text_short = join(', ', $short);
                    imagettftext($img, $font_size, $rotation, $x-3, $y+10, $red, $font_path, $text_short);
                }

            }
        }, $this->_plot);

        return $this;
    }


    // Volume
    protected function mode_bars(array &$item)
    {
        //dump($item['values']);
        $this->colors = ['#ff0000f2', '#00ff00f2'];
        $this->_plot->SetTickLabelColor($this->colors[1]);

        $this->_plot->SetXTickLabelPos('none');

        $this->_plot->SetDataType('text-data');

        // Controls the amount of extra space within each group of bars.
        // Default is 0.5, meaning 1/2 of the width of one bar is left as a
        // gap, within the space allocated to the group (see group_frac_width).
        // Increasing this makes each group of bars shrink together. Decreasing
        // this makes the group of bars expand within the allocated space.
        $this->_plot->bar_extra_space = .1;

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

        // 3D look
        $this->_plot->SetShading('none');

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
        $this->_plot->SetXAxisPosition(0);

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


    protected function mode_imagemap($item)
    {
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
            error_log('PHPlot::createDataArray() no candles');
            return $this;
        }

        if (!count($times = $candles->extract('time', 'sequential', true, $this->getParam('density_cutoff')))) {
            error_log('PHPlot::createDataArray() could not extract times');
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
                }
                else {
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

            $sig = $ind->getSignature();
            $item = [
                'class' => $ind->getShortClass(),
                'label' => 380 < $this->getParam('width') ?
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
                }
                // Right Y-axis items need individual min and max
                else {
                    $item['min'] = $values_min;
                    $item['max'] = $values_max;
                }
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
        if (!$this->getParam('autorefresh') ||
            !($refresh = $this->getParam('refresh'))) {
            return null;
        }
        if ($this->getCandles()->getParam('end')) {
            return null;
        }
        $refresh = "<script>window.waitForFinalEvent(function () {window.".
            $this->getParam('name').".refresh()}, ".
            ($refresh * 1000).", 'refresh".
            $this->getParam('name')."');";
        if ($this->last_close) {
            $refresh .= "document.title = '".number_format($this->last_close, 2).' - '.
                \Config::get('app.name', 'GTrader')."';";
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
        $this->_plot->setTitle($title);
        return $this;
    }


    protected function setPlotElements(string $dir = 'left', int $index = null, array $item)
    {
        if (0 >= count(Arr::get($item, 'values', []))) {
            return $this;
        }

        if ('left' === $dir) {

        } else {
            $this->_plot->SetDrawXGrid(false);
            $this->_plot->SetDrawYGrid(false);
            $this->_plot->SetXTickLabelPos('none');
        }


        $this->_plot->RemoveCallback('draw_all');
        $this->_plot->RemoveCallback('data_color');
        $this->_plot->RemoveCallback('data_points');

        $this->_plot->SetPlotBorderType('none');    // plot area border

        $this->_plot->SetDrawXAxis(false);          // X axis line
        $this->_plot->SetXTickPos('none');          // X tick marks
        $this->_plot->SetXDataLabelPos('none');     // X data labels

        $this->_plot->SetDrawYAxis(false);          // Y axis line

        $this->_plot->SetYTickLabelPos('none');
        $this->_plot->SetYDataLabelPos('none');

        $this->_plot->SetLineStyles(['solid']);
        $this->_plot->SetTickLabelColor('#555555');

        $this->_plot->SetYLabelType('data', $this->getParam('precision', 0)); // precision
        $this->_plot->SetMarginsPixels(30, 30, 15);
        $this->_plot->SetLegendStyle('left', 'left');
        //$this->_plot->SetLegendUseShapes(true);
        $this->_plot->SetLegendColorboxBorders('none');

        return $this;
    }



    protected function initPlotElements()
    {
        $this->getCandles()->reset(true);
        $start = ($first = $this->getCandles()->next()) ? $first->time : 0;
        $end = ($last = $this->getCandles()->last()) ? $last->time : 0;
        $longtime = 3600*24 < ($end - $start);
        $this->_plot->SetNumXTicks($xticks = floor($this->getParam('width', 200) / ($longtime ? 70 : 40)));
        $this->_plot->SetXLabelType('time', $longtime ? '%m-%d %H:%M' : '%H:%M');
        $this->_plot->SetYTickPos('none');

        return $this;
    }



    protected function setColors()
    {
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetLegendBgColor('black:50');
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
