<?php

namespace GTrader\Charts;

use GTrader\Chart;
use PHPlot_truecolor;

class PHPlot extends Chart {
    
    
    
    public function toHtml(array $params = [])
    {
        $width = isset($params['width']) ? $params['width'] : $this->getParam('width');
        $height = isset($params['height']) ? $params['height'] : $this->getParam('height');
        $plot = new PHPlot_truecolor($width, $height);
        $plot->SetPrintImage(false);
        $plot->SetFailureImage(false);
        $image_map = '<map name="map1">';
        $this->plotCandles($plot, $image_map);
        $image_map .= '</map>';
        foreach ($this->getIndicatorsVisibleSorted() as $ind)
        {
            //echo 'Plotting: '.$ind->getSignature();
            $ind->calculate();
            if ($ind->getParam('display.name') === 'Signals')
                $this->plotSignals($plot, $ind);
            else
                $this->plotIndicator($plot, $ind);
        }
        return $image_map.'<img class="img-responsive" src="'.$plot->EncodeImage().'" usemap="#map1">';
    }


    protected function plotCandles(&$plot, &$image_map = null)
    {
        $candles = $this->getCandles();
        $candles->reset();
        $title = 'R: '.$this->getParam('resolution').' '.
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
        $plot->setTitle($title);
        $plot->SetDataType('data-data');
        $plot->SetDataValues($price);
        $plot->setPlotType('candles' === $plot_type ? 'candlesticks2' : 'linepoints');
        $plot->setPointShapes('none');
        $plot->SetCallback('data_points', 
            function ($im, $junk, $shape, $row, $col, $x1, $y1, $x2 = null, $y2 = null) 
                        use (&$image_map, $times) {
                if (!$image_map) return null;
                //dump($row.$image_map);
                # Title, also tool-tip text:
                $title = gmdate('Y-m-d H:i', $times[$row]);
                # Required alt-text:
                $alt = $title;
                # Link URL, for demonstration only:
                $href = "javascript:console.log('".$times[$row]."')";
                if ('rect' == $shape)
                {
                    # Convert coordinates to integers:
                    $coords = sprintf("%d,%d,%d,%d", $x1, $y1, $x2, $y2);
                }
                else
                {
                    # Convert coordinates to integers:
                    $coords = sprintf("%d,%d,%d", $x1, $y1, 10);
                    $shape = 'circle';
                }
                # Append the record for this data point shape to the image map string:
                $image_map .= "<area shape=\"$shape\" coords=\"$coords\""
                           .  " title=\"$title\" alt=\"$alt\" href=\"$href\">\n";
            });
        $plot->SetMarginsPixels(30, 30, 15);
        $plot->SetXTickLabelPos('plotdown');
        $plot->SetLegend('candles' === $plot_type ? ['Price', ''] : ['Price']);
        $plot->SetLegendPixels(35, Chart::nextLegendY());
        $plot->SetDrawXGrid(true);
        $plot->SetBackgroundColor('black');
        $plot->SetGridColor('DarkGreen:100');
        $plot->SetLightGridColor('DimGrey:120');
        $plot->setTitleColor('DimGrey:80');
        $plot->SetTickColor('DarkGreen');
        $plot->SetTextColor('grey');
        $plot->SetDataColors(['red:30', 'DarkGreen:20','grey:90', 'grey:90']);
        //$plot->SetLineWidths([1, 1, 1, 1]);
        $plot->SetXLabelType('time', '%m-%d %H:%M');
        $plot->SetLineWidths('candles' === $plot_type ? 1 : 2);
        $plot->TuneYAutoRange(0);
        $plot->DrawGraph();
        $plot->RemoveCallback('data_points');
        if ('candles' === $plot_type)
            self::nextLegendY();
        return $this;
    }
    
    
    protected function plotIndicator(&$plot, $indicator)
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
        $plot->SetDataValues($data);
        $plot->SetLineWidths(2);
        $plot->setPlotType('lines');
        $plot->SetDataColors([$color]);
        if (isset($display['y_axis_pos']))
            if ($display['y_axis_pos'] === 'right')
            {
                $plot->SetPlotAreaWorld();
                $plot->SetYTickPos('plotright');
                $plot->SetYTickLabelPos('plotright');
                $plot->TuneYAutoRange(0);
            }
        $plot->SetLegendPixels(35, self::nextLegendY());
        $legend = $display['name'];
        if (count($params)) $legend .= ' ('.join(', ', $params).')';
        $plot->SetLegend([$legend]);
        $plot->DrawGraph();
        return $this;
    }
    
    
    public function plotSignals(&$plot, $indicator)
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
        $plot->SetDataColors(['green:50', 'red:50']);
        $plot->SetCallback('data_color',
               function($img, $junk, $row, $col, $extra = 0) use ($signals) {
                   //dump('R: '.$row.' C: '.$col.' E:'.$extra);
                   if ('buy' === $signals[$row]) return (0 === $extra) ? 1 : 0;
                   else if ('sell' === $signals[$row]) return (0 === $extra) ? 0 : 1;
                   else throw new \Exception('Oops, unmatched signal');
               });
        $plot->SetDataValues($data);
        $plot->SetLineWidths(1);
        $plot->SetPlotType('linepoints');
        $plot->SetPointShapes('dot');
        $plot->SetLineStyles(['dashed']);
        $plot->SetPointSizes(14);
        $plot->SetYDataLabelPos('plotin');
        $plot->DrawGraph();
        $plot->SetYDataLabelPos('none');
        $plot->SetLineStyles(['solid']);
        $plot->RemoveCallback('data_color');
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
