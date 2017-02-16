<?php

namespace GTrader\Charts;

use Illuminate\Http\Request;
use GTrader\Chart;
use GTrader\Exchange;
use PHPlot_truecolor;

class PHPlot extends Chart {

    protected $_plot;
    protected $_image_map;



    public function toHTML(string $content = '')
    {

        $this->addPageElement('stylesheets',
                    '<link href="'.mix('/css/PHPlot.css').'" rel="stylesheet">', true);

        $content = view('Charts/PHPlot', ['id' => $this->getParam('id')]);

        $content = parent::toHTML($content);

        $this->addPageElement('scripts_bottom',
                    '<script src="'.mix('/js/PHPlot.js').'"></script>', true);

        return $content;
    }


    public function toJSON($options = 0)
    {
        $width = $this->getParam('width');
        if ($width < 100) $width = 100;
        $height = $this->getParam('height');
        if ($height < 100) $height = 100;
        $this->_plot = new PHPlot_truecolor($width, $height);
        $this->_plot->SetPrintImage(false);
        $this->_plot->SetFailureImage(false);
        $this->_image_map = '<map name="map1">';
        $this->plotCandles();
        $this->_image_map .= '</map>';
        foreach ($this->getIndicatorsVisibleSorted() as $ind)
        {
            error_log('Plotting: '.$ind->getSignature());
            $ind->checkAndRun();
            if ($ind->getParam('display.name') === 'Signals')
                $this->plotSignals($ind);
            else
                $this->plotIndicator($ind);
        }
        $html = $this->_image_map.'<img class="img-responsive" src="'.
                $this->_plot->EncodeImage().'" usemap="#map1">';

        $o = json_decode(parent::toJSON($options));

        $o->html = $html;

        return json_encode($o, $options);
    }







    public function handleJSONRequest(Request $request)
    {
        $this->setParam('width', isset($request->width) ? $request->width : 800);
        $this->setParam('height', isset($request->height) ? $request->height : 600);
        $candles = $this->getCandles();
        $request = $this->sanityCheckRequest($request);
        foreach (['start', 'end', 'resolution', 'limit', 'exchange', 'symbol'] as $param)
            if (isset($request->$param))
                $candles->setParam($param, $request->$param);
        if (isset($request->command))
            $this->handleCommand($request->command, (array)json_decode($request->args));
        return $this->toJSON();

    }


    private function sanityCheckRequest(Request $request)
    {
        return $request;
    }


    private function handleCommand(string $command, array $args = [])
    {
        error_log('Command: '.$command.' args: '.serialize($args));
        $candles = $this->getCandles();
        $start = $candles->getParam('start');
        $end = $candles->getParam('end');
        $limit = $end - $start;
        $resolution = $candles->getParam('resolution');
        $live = $end > $candles->getLastInSeries() - $resolution;
        error_log('handleCommand live: '.$live.' start: '.$start.' end: '.$end);
        switch ($command)
        {
            case 'ESR':;
                $start = 0;
                $new_limit = floor($limit / $resolution);
                foreach (['exchange', 'symbol', 'resolution'] as $arg)
                    if (isset($args[$arg]))
                        $candles->setParam($arg, $args[$arg]);
                break;

            case 'backward':
                $epoch = $candles->getEpoch();
                $start -= floor($limit / 2);
                if ($start < $epoch)
                    $start = $epoch;
                $end = $start + $limit;
                break;

            case 'forward':
                $last = $candles->getLastInSeries();
                $end += floor($limit / 2);
                if ($end > $last)
                    $end = $last;
                $start = $end - $limit;
                break;

            case 'zoomIn':
                if ($live)
                    $start = $end - floor(($end - $start) / 2);
                else
                {
                    $mid = $start + floor($limit / 2);
                    $fourth = floor($limit / 4);
                    $start = $mid - $fourth;
                    $end = $mid + $fourth;
                }
                break;

            case 'zoomOut':
                if ($live)
                {
                    $start = $end - ($end - $start) * 3;
                    $new_limit = floor($limit / $resolution) * 3;
                }
                else
                {
                    $epoch = $candles->getEpoch();
                    $mid = $start + floor($limit / 2);
                    $start = $mid - $limit;
                    if ($start < $epoch)
                        $start = $epoch;
                    $end = $start + $limit * 2;
                    if ($end > time())
                        $end = time();
                }
                break;
        }
        $candles->setParam('start', $start);
        $candles->setParam('end', $end);
        if (isset($new_limit)) $candles->setParam('limit', $new_limit);
        //error_log('('.date('Y-m-d H:i', $candles->getEpoch()).') '.date('Y-m-d H:i', $start).' - '.date('Y-m-d H:i', $end));
    }


    protected function plotCandles()
    {
        $candles = $this->getCandles();
        if (!$candles->size())
            return $this;
        $candles->reset();
        $title = 'R: '.$candles->getParam('resolution').' '.
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
                    ['red:30', 'DarkGreen:20','grey:90', 'grey:90']:
                    'DarkGreen');
        $this->_plot->SetDataType('data-data');
        $this->_plot->SetDataValues($price);
        $this->_plot->setPlotType('candles' === $plot_type ? 'candlesticks2' : 'linepoints');
        $this->_plot->setPointShapes('none');
        $image_map = $this->_image_map;
        $this->_plot->SetCallback('data_points',
            function ($im, $junk, $shape, $row, $col, $x1, $y1, $x2 = null, $y2 = null)
                        use (&$image_map, $times, $price) {
                if (!$image_map) return null;
                //error_log($row.$image_map);
                # Title, also tool-tip text:
                $title = date('Y-m-d H:i', $times[$row]);
                $title .= ('rect' == $shape) ?
                            "\nO: ".$price[$row][2]
                            ."\nH: ".$price[$row][3]
                            ."\nL: ".$price[$row][4]
                            ."\nC: ".$price[$row][5] :
                            "\nC: ".$price[$row][2];
                # Link URL, for demonstration only:
                $href = "javascript:console.log('".$times[$row]."')";
                if ('rect' == $shape)
                {
                    # Convert coordinates to integers:
                    $coords = sprintf("%d,%d,%d,%d", $x1, 1000, $x2, 0);
                }
                else
                {
                    # Convert coordinates to integers:
                    $coords = sprintf("%d,%d,%d", $x1, $y1, 10);
                    $shape = 'circle';
                }
                # Append the record for this data point shape to the image map string:
                $image_map .= "<area shape=\"$shape\" coords=\"$coords\""
                           .  " title=\"$title\" href=\"$href\">\n";
            });
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
        $this->_plot->SetDataColors(['green:50', 'red:50']);
        $this->_plot->SetCallback('data_color',
               function($img, $junk, $row, $col, $extra = 0) use ($signals) {
                   //dump('R: '.$row.' C: '.$col.' E:'.$extra);
                   if ('buy' === $signals[$row]) return (0 === $extra) ? 1 : 0;
                   else if ('sell' === $signals[$row]) return (0 === $extra) ? 0 : 1;
                   else error_log('Unmatched signal');
               });
        $this->_plot->SetDataValues($data);
        $this->_plot->SetLineWidths(1);
        $this->_plot->SetPlotType('linepoints');
        $this->_plot->SetPointShapes('dot');
        $this->_plot->SetLineStyles(['dashed']);
        $this->_plot->SetPointSizes(14);
        $this->_plot->SetYDataLabelPos('plotin');
        $this->_plot->DrawGraph();
        $this->_plot->SetYDataLabelPos('none');
        $this->_plot->SetLineStyles(['solid']);
        $this->_plot->RemoveCallback('data_color');
        return $this;
    }


    public static function nextColor()
    {
        static $index = 0;
        $colors = ['yellow:110', 'maroon:100', 'brown:70'];
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
