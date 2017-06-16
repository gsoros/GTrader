<?php

namespace GTrader\Charts;

use Illuminate\Http\Request;
use GTrader\Chart;
use GTrader\Exchange;
use GTrader\Page;
//use PHPlot_truecolor;
use GTrader\Util;

class PHPlot extends Chart
{

    protected $_image_map;
    protected $last_close;
    protected $world = [];


    public function toHTML(string $content = '')
    {
        $content = view(
            'Charts/PHPlot',
            [
                'name' => $this->getParam('name'),
                'disabled' => $this->getParam('disabled', [])
            ]
        );

        $content = parent::toHTML($content);

        return $content;
    }


    public function getImage()
    {
        $width = $this->getParam('width');
        $height = $this->getParam('height');
        //error_log('PHPlot::toJSON() W: '.$width.' H:'.$height);
        if ($width > 0 && $height > 0) {
            $image_map_disabled = in_array('map', $this->getParam('disabled', []));
            $this->initPlot($width, $height);
            $this->_plot->SetPrintImage(false);
            $this->_plot->SetFailureImage(false);
            $map_name = 'map-'.$this->getParam('name');
            if (!$image_map_disabled) {
                $this->_image_map = '<map name="'.$map_name.'">';
            }
            $this->plotCandles();
            if (!$image_map_disabled) {
                $this->_image_map .= '</map>';
            }
            foreach ($this->getIndicatorsVisibleSorted() as $ind) {
                $ind->checkAndRun();
                if ($ind->getParam('display.name') === 'Signals') {
                    $this->plotSignals($ind);
                } else {
                    $this->plotIndicator($ind);
                }
            }
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
            $map_str = $image_map_disabled ? '' : ' usemap="#'.$map_name.'"';
            error_log('PHPlot::getImage() memory used: '.Util::getMemoryUsage());
            return $this->_image_map.'<img class="img-responsive" src="'.
                    $this->_plot->EncodeImage().'"'.$map_str.'>'.$refresh;
        }
        return '';
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
        error_log('handleCommand live: '.$live.' end: '.$end.' limit: '.$limit);
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
    }


    protected function plotCandles()
    {
        $candles = $this->getCandles();
        if (!$candles->size()) {
            return $this;
        }
        $candles->reset(); // loads
        $title = in_array('title', $this->getParam('disabled', [])) ? '' :
            "\n".$candles->getParam('exchange').' '.
            $candles->getParam('symbol').' '.
            $candles->getParam('resolution').' '.
            date('Y-m-d H:i', $candles->next()->time).' - '.
            date('Y-m-d H:i', $candles->last()->time);
        // Candles need at least 4 pixels width
        $plot_type = $candles->size() < $this->getParam('width', 1024) / 4 ? 'candles' : 'line';
        $price = $times = [];
        $ymin = 0;
        $ymax = 0;
        $candles->reset();
        while ($c = $candles->next()) {
            $price[] = ('candles' === $plot_type) ?
                ['', $c->time, $c->open, $c->high, $c->low, $c->close]:
                ['', $c->time, $c->close];
            $times[] = $c->time;
            $this->last_close = $c->close;
            $cmin = min($c->open, $c->high, $c->low, $c->close);
            if (!$ymin) {
                $ymin = $cmin;
            }
            if ($cmin < $ymin) {
                $ymin = $cmin;
            }
            $ymax = max($ymax, max($c->open, $c->high, $c->low, $c->close));
        }
        if ($ymin <= 0) {
            $ymin = null;
        }
        if ($ymax <= 0) {
            $ymax = null;
        }
        $this->world = [
            'xmin' => $times[0] - $candles->getParam('resolution'),
            'xmax' => $times[count($times) - 1] + $candles->getParam('resolution'),
            'ymin' => intval($ymin),
            'ymax' => intval($ymax),
        ];
        $this->setWorld();
        $this->_plot->setTitle($title);
        $colors = 'candles' === $plot_type ?
            ['#b0100010', '#00600010','grey:90', 'grey:90'] :
            ['DarkGreen'];
        $highlight_colors = ['yellow', 'red', 'blue'];
        $highlight_color_count = count($highlight_colors);
        $this->_plot->SetDataColors(array_merge($colors, $highlight_colors));
        $this->_plot->SetDataType('data-data');
        $this->_plot->SetDataValues($price);
        $this->_plot->setPlotType('candles' === $plot_type ? 'candlesticks2' : 'linepoints');
        $this->_plot->setPointShapes('none');

        $highlight = $this->getParam('highlight', []);
        if (count($highlight)) {
            $this->_plot->SetCallback(
                'data_color',
                function (
                    $img,
                    $junk,
                    $row,
                    $col,
                    $extra = 0) use (
                    $highlight,
                    $plot_type,
                    $times,
                    $highlight_color_count
                ) {
                    $return = ('candles' === $plot_type) ? 1 : 0;
                    $high_index = 0;
                    foreach ($highlight as $high_range) {
                        if (($times[$row] >= $high_range['start']) && ($times[$row] <= $high_range['end'])) {
                            $return = (('candles' === $plot_type) ? 4 : 1) + $high_index;
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

        $image_map = $this->_image_map;
        $image_map_disabled = in_array('map', $this->getParam('disabled', []));
        if (!$image_map_disabled) {
            $this->_plot->SetCallback(
                'data_points',
                function ($im, $junk, $shape, $row, $col, $x1, $y1, $x2 = null, $y2 = null) use
                    (&$image_map, $times, $price) {
                    if (!$image_map) {
                        return null;
                    }
                    //error_log($row.$image_map);
                    $title = 'T: '.date('Y-m-d H:i T', $times[$row]);
                    $title .= ('rect' == $shape) ?
                        "\nO: ".$price[$row][2]
                        ."\nH: ".$price[$row][3]
                        ."\nL: ".$price[$row][4]
                        ."\nC: ".$price[$row][5] :
                        "\nC: ".$price[$row][2];
                    $href = "javascript:console.log('".$times[$row]."')";
                    if ('rect' == $shape) {
                        $coords = sprintf("%d,%d,%d,%d", $x1, 1000, $x2, 0);
                    } else {
                        $coords = sprintf("%d,%d,%d", $x1, $y1, 10);
                        $shape = 'circle';
                    }
                    # Append the record for this data point shape to the image map string:
                    $image_map .= "<area shape=\"$shape\" coords=\"$coords\""
                               .  " title=\"$title\" href=\"$href\">\n";
                }
            );
        }
        $this->_plot->SetMarginsPixels(30, 30, 15);
        $this->_plot->SetXTickLabelPos('plotdown');
        $this->_plot->SetLegend('candles' === $plot_type ? ['Price', ''] : ['Price']);
        $this->_plot->SetLegendPixels(35, $this->nextLegendY());
        $this->_plot->SetDrawXGrid(true);
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetGridColor('DarkGreen:100');
        $this->_plot->SetLightGridColor('DimGrey:120');
        $this->_plot->setTitleColor('DimGrey:80');
        $this->_plot->SetTickColor('DarkGreen');
        $this->_plot->SetTextColor('grey');
        //$plot->SetLineWidths([1, 1, 1, 1]);
        $this->_plot->SetXLabelType('time', '%m-%d %H:%M');
        $this->_plot->SetLineWidths('candles' === $plot_type ? 1 : 2);
        //$this->_plot->SetYScaleType('log');
        $this->_plot->TuneYAutoRange(0);
        $this->_plot->DrawGraph();
        $this->_image_map = $image_map;
        if (!$image_map_disabled) {
            $this->_plot->RemoveCallback('data_points');
        }
        if ('candles' === $plot_type) {
            $this->nextLegendY();
        }
        return $this;
    }


    protected function setWorld(string $axes='xy')
    {
        $xmin = $ymin = $xmax = $ymax = null;
        if (strstr($axes, 'x')) {
            $xmin = $this->world['xmin'];
            $xmax = $this->world['xmax'];
        }
        if (strstr($axes, 'y')) {
            $ymin = $this->world['ymin'];
            $ymax = $this->world['ymax'];
        }
        $this->_plot->setPlotAreaWorld(
            $xmin,
            $ymin,
            $xmax,
            $ymax
        );
        return $this;
    }


    protected function plotIndicator($indicator)
    {
        $display = $indicator->getParam('display');
        $params = $indicator->getParam('indicator');
        $candles = $this->getCandles();

        $first_output = true;
        $outputs = $indicator->getParam('outputs', ['']);
        $world_set = false;
        $data = [];
        $colors = [];
        $last_color = null;

        foreach ($outputs as $output_index => $output_name) {
            $sig = $indicator->getSignature();
            if ($output_name) {
                $sig .= '_'.$output_name;
            }
            $index = 0;
            $candles->reset();
            while ($candle = $candles->next()) {
                $index++;
                $value = isset($candle->$sig) ? $candle->$sig : '';
                if (isset($data[$index-1])) {
                    $data[$index-1][] = $value;
                    continue;
                }
                $data[$index-1] =  ['', $candle->time, $value];
            }
            $last_color = self::nextColor();
            $colors[] = $last_color;
            if ($first_output) {
                $legend = [$indicator->getDisplaySignature().' '.$output_name];
            }
            else {
                $legend[] = $output_name;
            }
            $first_output = false;
        }

        if (!count($data)) {
            return $this;
        }

        $this->_plot->SetDataValues($data);
        $this->_plot->SetLineWidths(2);
        $this->_plot->setPlotType('lines');
        $this->_plot->SetDataColors($colors);
        $this->_plot->SetTickLabelColor($last_color);
        if (!$world_set) {
            if (isset($display['y_axis_pos'])) {
                if ($display['y_axis_pos'] === 'right') {
                    $this->setWorld('x');
                    $world_set = true;
                    $this->_plot->SetYTickPos('plotright');
                    $this->_plot->SetYTickLabelPos('plotright');
                    $this->_plot->TuneYAutoRange(0);
                }
            }
        }

        if (!$world_set) {
            $this->setWorld();
        }

        $this->_plot->SetLegendPixels(35, self::nextLegendY(count($outputs)));
        $this->_plot->SetLegend($legend);

        $this->_plot->DrawGraph();


        return $this;
    }


    public function plotSignals($indicator)
    {
        $sig = $indicator->getSignature();

        $candles = $this->getCandles();
        $candles->reset();
        $data = $signals = [];
        //dd($this);
        while ($candle = $candles->next()) {
            if (isset($candle->$sig)) {
                $data[] = ['', $candle->time, round($candle->$sig['price'], 2)];
                $signals[] = $candle->$sig['signal'];
            }
        }
        //dd($signals);
        if (!count($data)) {
            return $this;
        }
        $this->_plot->SetDataColors(['#00ff0050', '#ff000010']);
        $this->_plot->SetCallback(
            'data_color',
            function ($img, $junk, $row, $col, $extra = 0) use ($signals) {
                //dump('R: '.$row.' C: '.$col.' E:'.$extra);
                if ('long' === $signals[$row]) {
                    return (0 === $extra) ? 1 : 0;
                } elseif ('short' === $signals[$row]) {
                    return (0 === $extra) ? 0 : 1;
                }
                error_log('Unmatched signal');
            }
        );
        $this->_plot->SetDataValues($data);
        $this->_plot->SetLineWidths(1);
        $this->_plot->SetPlotType('linepoints');
        $this->_plot->SetPointShapes('target');
        $this->_plot->SetLineStyles(['dashed']);
        $this->_plot->SetPointSizes(floor($this->getParam('width', 1024) / 100));
        $this->_plot->SetYDataLabelPos('plotin');
        $this->_plot->SetLegendPixels(35, self::nextLegendY());
        $this->_plot->SetYTickLabelPos('none');
        $legend = $indicator->getParam('display.name');
        $params = $indicator->getParam('indicator');
        if (count($params)) {
            $legend .= ' ('.join(', ', $params).')';
        }
        $this->_plot->SetLegend([$legend, '']);
        $this->setWorld();
        $this->_plot->DrawGraph();
        self::nextLegendY();
        $this->_plot->SetYDataLabelPos('none');
        $this->_plot->SetLineStyles(['solid']);
        $this->_plot->RemoveCallback('data_color');
        return $this;
    }
}
