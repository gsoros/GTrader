<?php

namespace GTrader;

use PHPlot_truecolor;
use Illuminate\Support\Arr;

class Plot extends Base
{
    protected $PHPlot;
    protected static $colorIndex = 0;
    protected static $legendIndex = 0;
    protected $world = [];

    public function __construct(array $params = [])
    {
        static::$colorIndex = 0;
        static::$legendIndex = 0;

        parent::__construct($params);
    }


    public function toHTML(string $content = '')
    {
        return $this->getImage();
    }


    public function getImage()
    {
        $data = $this->getParam('data');
        if (!is_array($data)) {
            Log::error('Data is not an array.');
            return '';
        }
        if (!count($data)) {
            Log::error('Data is empty.');
            return '';
        }

        $this->initPlot();

        $this->plot($data);
        //return '<img class="img-responsive" src="'.
        return '<img src="'.$this->PHPlot->EncodeImage().'">';
    }


    protected function initPlot()
    {
        $width = intval($this->getParam('width'));
        $height = intval($this->getParam('height'));
        if ($width <= 1 || $height <= 1) {
            Log::error('Missing width or height.');
            return null;
        }
        if (!function_exists('imagecreatetruecolor')) {
            Log::error('Function imagecreatetruecolor is missing');
            dump('Plot::initPlot(): function imagecreatetruecolor is missing. GD support is required by PHPlot.');
            return null;
        }
        $this->PHPlot = new PHPlot_truecolor($width, $height);
        $this->PHPlot->SetPrintImage(false);
        $this->PHPlot->SetFailureImage(false);
        return $this;
    }


    protected function plot(array $data)
    {
        $this->PHPlot->SetMarginsPixels(30, 30, 15);
        $this->PHPlot->SetBackgroundColor('black');
        $this->PHPlot->SetGridColor('DarkGreen:100');
        $this->PHPlot->SetLightGridColor('DimGrey:120');
        $this->PHPlot->setTitleColor('DimGrey:80');
        $this->PHPlot->SetTickColor('DarkGreen');
        $this->PHPlot->SetTextColor('#999999');
        $this->PHPlot->SetDataType('data-data');
        $this->PHPlot->SetLegendStyle('left', 'left');
        $this->PHPlot->SetLegendColorboxBorders('none');

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
                //Log::debug($dir.' label: '.$label);
                if (!count($values)) {
                    continue;
                }
                $color = self::nextColor();
                $this->PHPlot->SetDataValues($values);
                $this->PHPlot->SetLineWidths(Arr::get($data, $label.'.display.stroke', 2));
                $this->PHPlot->setPlotType('lines');
                $this->PHPlot->SetDataColors([$color]);
                $this->PHPlot->SetTickLabelColor($color);

                if ('right' === $dir) {
                    //$this->setWorld($out[$dir]['dim'], 'x');
                    $this->PHPlot->SetYTickPos('plotright');
                    $this->PHPlot->SetYTickLabelPos('plotright');
                    //$this->PHPlot->TuneYAutoRange(0);
                }

                //$this->PHPlot->TuneYAutoRange(0);
                $this->PHPlot->SetLegendPixels(35, self::nextLegendY());
                $this->PHPlot->SetLegend([$label]);
                $this->PHPlot->DrawGraph();
            }
        }
        return $this;
    }

    public static function nextColor()
    {
        $colors = [
            'orange:80',
            'yellow:110',
            'maroon:70',
            'brown:70',
            '#22226640',
            'pink:100',
            'cyan:100',
            'blue:90',
        ];
        $color = $colors[static::$colorIndex];
        static::$colorIndex ++;
        if (static::$colorIndex >= count($colors)) {
            static::$colorIndex = 0;
        }
        return $color;
    }

    public static function nextLegendY(int $step = 1)
    {
        $ret = static::$legendIndex;
        static::$legendIndex += (1 < $step ? 25 : 35) * $step;
        return $ret;
    }

    protected function setWorld(array $new_world = [], $set_axes = 'xy')
    {
        $this->world = array_replace($this->world, $new_world);

        //Log::debug('axes: '.$set_axes.' world: '.json_encode($world));

        if ('none' == $set_axes || !$set_axes) {
            return $this;
        }

        $xmin = $ymin = $xmax = $ymax = null;
        if (strstr($set_axes, 'x')) {
            $xmin = Arr::get($this->world, 'xmin');
            $xmax = Arr::get($this->world, 'xmax');
        }
        if (strstr($set_axes, 'y')) {
            $ymin = Arr::get($this->world, 'ymin');
            $ymax = Arr::get($this->world, 'ymax');
        }
        if ($xmin >= $xmax) {
            $xmax = $xmin + 1;
        }
        if ($ymin >= $ymax) {
            //Log::debug('axes: '.$set_axes.' ymin: '.$ymin.' ymax: '.$ymax.' world: '.json_encode($world));
            $ymax = $ymin + 1;
        }
        $this->PHPlot->setPlotAreaWorld(
            $xmin,
            $ymin,
            $xmax,
            $ymax
        );
        return $this;
    }
}
