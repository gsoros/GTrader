<?php

namespace GTrader\Charts;

use Illuminate\Http\Request;
use GTrader\Chart;
use GTrader\Exchange;
use GTrader\Page;
use PHPlot_truecolor;
//use GTrader\Util;

class PHPlot extends Chart {

    protected $_plot;
    protected $_image_map;



    public function toHTML(string $content = '')
    {
        $content = view('Charts/PHPlot', [
                    'name' => $this->getParam('name'),
                    'disabled' => $this->getParam('disabled', [])
                    ]);

        $content = parent::toHTML($content);

        return $content;
    }


    public function getImage() {

        $width = $this->getParam('width');
        $height = $this->getParam('height');
        //error_log('PHPlot::toJSON() W: '.$width.' H:'.$height);
        if ($width > 0 && $height > 0)
        {
            $image_map_disabled = in_array('map', $this->getParam('disabled', []));
            $this->_plot = new PHPlot_truecolor($width, $height);
            $this->_plot->SetPrintImage(false);
            $this->_plot->SetFailureImage(false);
            $map_name = 'map-'.$this->getParam('name');
            if (!$image_map_disabled)
                $this->_image_map = '<map name="'.$map_name.'">';
            $this->plotCandles();
            if (!$image_map_disabled)
                $this->_image_map .= '</map>';
            foreach ($this->getIndicatorsVisibleSorted() as $ind)
            {
                $ind->checkAndRun();
                if ($ind->getParam('display.name') === 'Signals')
                    $this->plotSignals($ind);
                else
                    $this->plotIndicator($ind);
            }
            $refresh = null;
            if ($this->getParam('autorefresh') &&
                ($refresh = $this->getParam('refresh')))
            {
                $refresh = "<script>window.waitForFinalEvent(function () {window.".
                            $this->getParam('name').".refresh()}, ".
                            ($refresh * 1000).", 'refresh".
                            $this->getParam('name')."')</script>";
            }
            $map_str = $image_map_disabled ? '' : ' usemap="#'.$map_name.'"';
            return $this->_image_map.'<img class="img-responsive" src="'.
                    $this->_plot->EncodeImage().'"'.$map_str.'>'.$refresh;
        }
        return '';
    }


    public Function addPageElements()
    {
        parent::addPageElements();

        Page::add('stylesheets',
                    '<link href="'.mix('/css/PHPlot.css').'" rel="stylesheet">');
        Page::add('scripts_top',
                    '<script src="/js/PHPlot.js"></script>');
        return $this;
    }



    public function handleImageRequest(Request $request)
    {
        $this->setParam('width', isset($request->width) ? $request->width : 0);
        $this->setParam('height', isset($request->height) ? $request->height : 0);
        $candles = $this->getCandles();
        foreach (['start', 'end', 'resolution', 'limit', 'exchange', 'symbol'] as $param)
            if (isset($request->$param))
                $candles->setParam($param, $request->$param);
        if (isset($request->command))
            $this->handleCommand($request->command, (array)json_decode($request->args));
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
        switch ($command)
        {
            case 'ESR':;
                foreach (['exchange', 'symbol', 'resolution'] as $arg)
                    if (isset($args[$arg]))
                        $candles->setParam($arg, $args[$arg]);
                break;

            case 'backward':
                if ($limit)
                {
                    $epoch = $candles->getEpoch();
                    if (!$end)
                        $end = $last;
                    $end -= floor($limit * $resolution / 2);
                    if (($end - $limit * $resolution) < $epoch)
                        $end = $epoch + $limit * $resolution;
                }
                break;

            case 'forward':
                if ($end)
                {
                    $end += floor($limit * $resolution / 2);
                    if ($end > $last)
                        $end = 0;
                }
                break;

            case 'zoomIn':
                if (!$limit && $resolution)
                {
                    $epoch = $candles->getEpoch();
                    $limit = floor(($last - $epoch) / $resolution);
                }
                $limit = ceil($limit / 2);
                if ($limit < 10)
                    $limit = 10;
                break;

            case 'zoomOut':
                $epoch = $candles->getEpoch();
                $limit = $limit * 2;
                if ($limit > (($last - $epoch) / $resolution))
                    $limit = 0;
                break;
        }
        $candles->setParam('limit', $limit);
        $candles->setParam('end', $end);
    }


    protected function plotCandles()
    {
        $candles = $this->getCandles();
        if (!$candles->size())
            return $this;
        $candles->reset(); // loads
        $title = in_array('title', $this->getParam('disabled', [])) ? '' :
            'R: '.$candles->getParam('resolution').' '.
                 date('Y-m-d H:i', $candles->next()->time).' - '.
                 date('Y-m-d H:i', $candles->last()->time);
        $plot_type = $candles->size() < 260 ? 'candles' : 'line';
        $price = $times = [];
        $candles->reset();
        while ($c = $candles->next())
        {
            $price[] = ('candles' === $plot_type) ?
                ['', $c->time, $c->open, $c->high, $c->low, $c->close]:
                ['', $c->time, $c->close];
                $times[] = $c->time;
        }
        $this->_plot->setTitle($title);
        $this->_plot->SetDataColors(
                    'candles' === $plot_type ?
                    ['#b0100010', '#00600010','grey:90', 'grey:90', 'yellow']:
                    ['DarkGreen', 'yellow']);
        $this->_plot->SetDataType('data-data');
        $this->_plot->SetDataValues($price);
        $this->_plot->setPlotType('candles' === $plot_type ? 'candlesticks2' : 'linepoints');
        $this->_plot->setPointShapes('none');

        $highlight = $this->getParam('highlight', []);
        if (2 === count($highlight))
        {
            $this->_plot->SetCallback('data_color',
                function($img, $junk, $row, $col, $extra = 0) use ($highlight, $plot_type, $times) {
                    if (($times[$row] >= $highlight[0]) && ($times[$row] <= $highlight[1]))
                        return ('candles' === $plot_type) ? 4 : 1;
                    else
                        return ('candles' === $plot_type) ? 1 : 0;
                });
        }

        $image_map = $this->_image_map;
        $image_map_disabled = in_array('map', $this->getParam('disabled', []));
        if (!$image_map_disabled)
        {
            $this->_plot->SetCallback('data_points',
                function ($im, $junk, $shape, $row, $col, $x1, $y1, $x2 = null, $y2 = null)
                            use (&$image_map, $times, $price) {
                    if (!$image_map) return null;
                    //error_log($row.$image_map);
                    $title = 'T: '.date('Y-m-d H:i T', $times[$row]);
                    $title .= ('rect' == $shape) ?
                                "\nO: ".$price[$row][2]
                                ."\nH: ".$price[$row][3]
                                ."\nL: ".$price[$row][4]
                                ."\nC: ".$price[$row][5] :
                                "\nC: ".$price[$row][2];
                    $href = "javascript:console.log('".$times[$row]."')";
                    if ('rect' == $shape)
                    {
                        $coords = sprintf("%d,%d,%d,%d", $x1, 1000, $x2, 0);
                    }
                    else
                    {
                        $coords = sprintf("%d,%d,%d", $x1, $y1, 10);
                        $shape = 'circle';
                    }
                    # Append the record for this data point shape to the image map string:
                    $image_map .= "<area shape=\"$shape\" coords=\"$coords\""
                               .  " title=\"$title\" href=\"$href\">\n";
                });
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
        $this->_plot->TuneYAutoRange(0);
        $this->_plot->DrawGraph();
        $this->_image_map = $image_map;
        if (!$image_map_disabled)
            $this->_plot->RemoveCallback('data_points');
        if ('candles' === $plot_type)
            $this->nextLegendY();
        return $this;
    }


    protected function plotIndicator($indicator)
    {
        $display = $indicator->getParam('display');
        $params = $indicator->getParam('indicator');
        $sig = $indicator->getSignature();
        $color = self::nextColor();
        $candles = $this->getCandles();
        $candles->reset();
        $data = [];
        while ($candle = $candles->next())
            $data[] = ['', $candle->time, isset($candle->$sig) ? $candle->$sig : ''];
        if (!count($data))
            return $this;
        $this->_plot->SetDataValues($data);
        $this->_plot->SetLineWidths(2);
        $this->_plot->setPlotType('lines');
        $this->_plot->SetDataColors([$color]);
        if (isset($display['y_axis_pos']))
            if ($display['y_axis_pos'] === 'right')
            {
                $this->_plot->SetPlotAreaWorld();
                $this->_plot->SetYTickPos('plotright');
                $this->_plot->SetYTickLabelPos('plotright');
                $this->_plot->TuneYAutoRange(0);
            }
        $this->_plot->SetLegendPixels(35, self::nextLegendY());
        $legend = $display['name'];
        if (count($params)) $legend .= ' ('.join(', ', $params).')';
        $this->_plot->SetLegend([$legend]);
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
        while ($candle = $candles->next())
            if (isset($candle->$sig))
            {
                $data[] = ['', $candle->time, $candle->$sig['price']];
                $signals[] = $candle->$sig['signal'];
            }
        //dd($signals);
        if (!count($data))
            return $this;
        $this->_plot->SetDataColors(['#00ff0050', '#ff000010']);
        $this->_plot->SetCallback('data_color',
               function($img, $junk, $row, $col, $extra = 0) use ($signals) {
                   //dump('R: '.$row.' C: '.$col.' E:'.$extra);
                   if ('long' === $signals[$row]) return (0 === $extra) ? 1 : 0;
                   else if ('short' === $signals[$row]) return (0 === $extra) ? 0 : 1;
                   else error_log('Unmatched signal');
               });
        $this->_plot->SetDataValues($data);
        $this->_plot->SetLineWidths(1);
        $this->_plot->SetPlotType('linepoints');
        $this->_plot->SetPointShapes('target');
        $this->_plot->SetLineStyles(['dashed']);
        $this->_plot->SetPointSizes(14);
        $this->_plot->SetYDataLabelPos('plotin');
        $this->_plot->SetLegendPixels(35, self::nextLegendY());
        $legend = $indicator->getParam('display.name');
        $params = $indicator->getParam('indicator');
        if (count($params)) $legend .= ' ('.join(', ', $params).')';
        $this->_plot->SetLegend([$legend, '']);
        $this->_plot->DrawGraph();
        self::nextLegendY();
        $this->_plot->SetYDataLabelPos('none');
        $this->_plot->SetLineStyles(['solid']);
        $this->_plot->RemoveCallback('data_color');
        return $this;
    }


    public static function nextColor()
    {
        static $index = 0;
        $colors = ['#22226640', 'yellow:110', 'maroon:100', 'brown:70'];
        $color = $colors[$index];
        $index ++;
        if ($index >= count($colors)) $index = 0;
        return $color;
    }

    public static function nextLegendY()
    {
        static $y = 20;
        $ret = $y;
        $y += 30;
        return $ret;
    }
}
