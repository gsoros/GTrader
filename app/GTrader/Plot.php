<?php

namespace GTrader;

use PHPlot_truecolor;
use Illuminate\Support\Arr;

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
        $data = $this->getParam('data');
        if (!is_array($data)) {
            error_log('Plot::getImage(): data is not an array.');
            return '';
        }
        if (!count($data)) {
            error_log('Plot::getImage(): data is empty.');
            return '';
        }

        $this->initPlot();

        $this->plot($data);
        //return '<img class="img-responsive" src="'.
        return '<img src="'.
                $this->_plot->EncodeImage().'">';
    }


    protected function initPlot()
    {
        $width = intval($this->getParam('width'));
        $height = intval($this->getParam('height'));
        if ($width <= 1 || $height <= 1) {
            error_log('Plot::initPlot(): Missing width or height.');
            return null;
        }
        if (!function_exists('imagecreatetruecolor')) {
            error_log('Plot::initPlot(): function imagecreatetruecolor is missing');
            dump('Plot::initPlot(): function imagecreatetruecolor is missing. GD support is required by PHPlot.');
            return null;
        }
        $this->_plot = new PHPlot_truecolor($width, $height);
        $this->_plot->SetPrintImage(false);
        $this->_plot->SetFailureImage(false);
        return $this;
    }


    protected function plot(array $data)
    {
        $this->_plot->SetMarginsPixels(30, 30, 15);
        $this->_plot->SetBackgroundColor('black');
        $this->_plot->SetGridColor('DarkGreen:100');
        $this->_plot->SetLightGridColor('DimGrey:120');
        $this->_plot->setTitleColor('DimGrey:80');
        $this->_plot->SetTickColor('DarkGreen');
        $this->_plot->SetTextColor('#999999');
        $this->_plot->SetDataType('data-data');
        $this->_plot->SetLegendStyle('left', 'left');
        $this->_plot->SetLegendColorboxBorders('none');

        $out = ['left' => [], 'right' => []];
        reset($data);
        while (list($label, $item) = each($data)) {
            if (!is_array($item)) {
                continue;
            }
            if (!is_array($values = $item['values'])) {
                continue;
            }
            if (!count($values)) {
                continue;
            }
            $dir = 'left';
            $other_dir = 'right';
            if ($ypos = Arr::get($item, 'display.y-axis')) {
                $other_dir = $ypos == $other_dir ? $dir : $other_dir;
                $dir = $ypos;
            }

            $out[$dir]['dim'] = [
                'xmin' => min(
                    min(array_keys($values)),
                    Arr::get($out, $dir.'.dim.xmin'),
                    Arr::get($out, $other_dir.'.dim.xmin')
                ),
                'xmax' => max(
                    max(array_keys($values)),
                    Arr::get($out, $dir.'.dim.xmax'),
                    Arr::get($out, $other_dir.'.dim.xmax')
                ),
                'ymin' => ($min = Arr::get($out, $dir.'.dim.ymin')) ? min(min($values), $min) : min($values),
                'ymax' => ($max = Arr::get($out, $dir.'.dim.ymax')) ? max(max($values), $max) : max($values),
            ];

            $out[$dir]['values'][$label] = [];
            reset($values);
            while (list($xvalue, $yvalue) = each($values)) {
                $out[$dir]['values'][$label][] = ['', $xvalue, $yvalue];
            }
        }

        foreach (['left', 'right'] as $dir) {
            if (!is_array($out[$dir])) {
                continue;
            }
            if (!count($out[$dir])) {
                continue;
            }
            if (!is_array($out[$dir]['values'])) {
                continue;
            }
            if (!count($out[$dir]['values'])) {
                continue;
            }
            $this->setWorld($out[$dir]['dim']);
            //dump($out);

            foreach ($out[$dir]['values'] as $label => $values) {
                //error_log($dir.' label: '.$label);
                if (!count($values)) {
                    continue;
                }
                $color = self::nextColor();
                $this->_plot->SetDataValues($values);
                $this->_plot->SetLineWidths(Arr::get($data, $label.'.display.stroke', 2));
                $this->_plot->setPlotType('lines');
                $this->_plot->SetDataColors([$color]);
                $this->_plot->SetTickLabelColor($color);

                if ('right' === $dir) {
                    //$this->setWorld($out[$dir]['dim'], 'x');
                    $this->_plot->SetYTickPos('plotright');
                    $this->_plot->SetYTickLabelPos('plotright');
                    //$this->_plot->TuneYAutoRange(0);
                }

                //$this->_plot->TuneYAutoRange(0);
                $this->_plot->SetLegendPixels(35, self::nextLegendY());
                $this->_plot->SetLegend([$label]);
                $this->_plot->DrawGraph();
            }
        }
        return $this;
    }

    public static function nextColor()
    {
        static $index = 0;
        $colors = [
            '#22226640',
            'yellow:110',
            'maroon:70',
            'brown:70',
            'pink:100',
            'cyan:100',
            'blue:90',
        ];
        $color = $colors[$index];
        $index ++;
        if ($index >= count($colors)) {
            $index = 0;
        }
        return $color;
    }

    public static function nextLegendY(int $step = 1)
    {
        static $y = 0;

        $ret = $y;
        $y += (1 < $step ? 25 : 35) * $step;
        return $ret;
    }

    protected function setWorld(array $new_world = [], $set_axes = 'xy')
    {
        static $world = [];

        $world = array_replace($world, $new_world);

        //error_log('setWorld() axes: '.$set_axes.' world: '.json_encode($world));

        if ('none' == $set_axes || !$set_axes) {
            return $this;
        }

        $xmin = $ymin = $xmax = $ymax = null;
        if (strstr($set_axes, 'x')) {
            $xmin = Arr::get($world, 'xmin');
            $xmax = Arr::get($world, 'xmax');
        }
        if (strstr($set_axes, 'y')) {
            $ymin = Arr::get($world, 'ymin');
            $ymax = Arr::get($world, 'ymax');
        }
        if ($xmin >= $xmax) {
            $xmax = $xmin + 1;
        }
        if ($ymin >= $ymax) {
            //error_log('setWorld() axes: '.$set_axes.' ymin: '.$ymin.' ymax: '.$ymax.' world: '.json_encode($world));
            $ymax = $ymin + 1;
        }
        $this->_plot->setPlotAreaWorld(
            $xmin,
            $ymin,
            $xmax,
            $ymax
        );
        return $this;
    }
}
