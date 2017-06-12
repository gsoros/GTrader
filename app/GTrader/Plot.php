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
            error_log('Plot::getImage(): Missing width or height.');
            return '';
        }
        $data = $this->getParam('data');
        if (!is_array($data)) {
            error_log('Plot::getImage(): data is not an array.');
            return '';
        }
        if (!count($data)) {
            error_log('Plot::getImage(): data is empty.');
            return '';
        }

        $this->initPlot($width, $height);
        $this->_plot->SetPrintImage(false);
        $this->_plot->SetFailureImage(false);
        $this->plot($data);
        return '<img class="img-responsive" src="'.
                $this->_plot->EncodeImage().'">';
    }


    protected function initPlot($width, $height)
    {
        $this->_plot = new PHPlot_truecolor($width, $height);
        return $this;
    }


    protected function plot(array $data)
    {
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetGridColor('DarkGreen:100');
        $this->_plot->SetLightGridColor('DimGrey:120');
        $this->_plot->setTitleColor('DimGrey:80');
        $this->_plot->SetTickColor('DarkGreen');
        $this->_plot->SetTextColor('grey');
        $this->_plot->SetDataType('data-data');

        $out = [];
        $xmax = 0;
        reset($data);
        while (list($label, $values) = each($data)) {
            if (!is_array($values)) {
                continue;
            }
            if (!count($values)) {
                continue;
            }
            if (($xmax_tmp = max(array_keys($values))) > $xmax) {
                $xmax = $xmax_tmp;
            }
            $xmin_tmp = min(array_keys($values));
            if (!isset($xmin)) {
                $xmin = $xmin_tmp;
            } else {
                $xmin = min($xmin, $xmin_tmp);
            }
            $out[$label] = [];
            reset($values);
            while (list($xvalue, $yvalue) = each($values)) {
                $out[$label][] = ['', $xvalue, $yvalue];
            }
        }
        if (!isset($xmin)) {
            return $this;
        }
        if ($xmin == $xmax) {
            return $this;
        }
        foreach ($out as $label => $values) {
            if (!count($values)) {
                continue;
            }
            $color = self::nextColor();
            $this->_plot->SetDataValues($values);
            $this->_plot->SetLineWidths(2);
            $this->_plot->setPlotType('lines');
            $this->_plot->SetDataColors([$color]);
            $this->_plot->SetTickLabelColor($color);
            $this->_plot->SetPlotAreaWorld($xmin, null, $xmax);
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

    public static function nextLegendY(int $step = 1)
    {
        static $y = 20;
        $ret = $y;
        $y += 20 * $step;
        return $ret;
    }
}
