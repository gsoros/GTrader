<?php

namespace GTrader;

use PHPlot_truecolor;

class Plot
{
    use Skeleton;

    protected $_plot;

    public function toHTML(string $content = '')
    {
        return $this->getImage();
    }


    public function getImage()
    {
        $width = $this->getParam('width');
        $height = $this->getParam('height');
        if ($width <= 1 || $height <= 1) {
            return 'Plot::getImage(): Missing width or height.';
        }
        $labels = $this->getParam('labels');
        if (!is_array($labels)) {
            return 'Plot::getImage(): labels is not an array.';
        }
        if (!count($labels)) {
            return 'Plot::getImage(): labels is empty.';
        }
        $values = $this->getParam('values');
        if (!is_array($values)) {
            return 'Plot::getImage(): values is not an array.';
        }
        if (!count($values)) {
            return 'Plot::getImage(): values is empty.';
        }

        $this->_plot = new PHPlot_truecolor($width, $height);
        $this->_plot->SetPrintImage(false);
        $this->_plot->SetFailureImage(false);
        $this->plot($labels, $values);
        return '<img class="img-responsive" src="'.
                $this->_plot->EncodeImage().'">';
    }



    protected function plot(array $labels, array $values)
    {
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetGridColor('DarkGreen:100');
        $this->_plot->SetLightGridColor('DimGrey:120');
        $this->_plot->setTitleColor('DimGrey:80');
        $this->_plot->SetTickColor('DarkGreen');
        $this->_plot->SetTextColor('grey');
        $this->_plot->SetDataType('data-data');

        $data = [];
        $xmax = 0;
        reset($labels);
        while (list($index, $label) = each($labels)) {
            if (!is_array($values[$index])) {
                continue;
            }
            $data[$label] = [];
            reset($values[$index]);
            while (list($xvalue, $yvalue) = each($values[$index])) {
                $data[$label][] = ['', $xvalue, $yvalue];
                if ($xvalue > $xmax) {
                    $xmax = $xvalue;
                }
            }
        }
        foreach ($data as $label => $values) {
            if (!count($values)) {
                continue;
            }
            $color = self::nextColor();
            $this->_plot->SetDataValues($values);
            $this->_plot->SetLineWidths(2);
            $this->_plot->setPlotType('lines');
            $this->_plot->SetDataColors([$color]);
            $this->_plot->SetTickLabelColor($color);
            $this->_plot->SetPlotAreaWorld(0, null, $xmax);
            $this->_plot->TuneYAutoRange(0);
            $this->_plot->SetLegendPixels(35, self::nextLegendY());
            $this->_plot->SetLegend([$label]);
            $this->_plot->DrawGraph();
        }
        return $this;
    }

    public static function nextColor()
    {
        static $index = 0;
        $colors = ['#22226640', 'yellow:110', 'maroon:100', 'brown:70'];
        $color = $colors[$index];
        $index ++;
        if ($index >= count($colors)) {
            $index = 0;
        }
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
