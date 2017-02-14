<?php

namespace GTrader;

use Illuminate\Http\Request;
use GTrader\Exchange;
use GTrader\Strategy;


abstract class Chart extends Skeleton {
    use HasCandles;

    protected $_strategy;
    protected $_page_elements = [
                    'scripts_top' => [],
                    'scripts_bottom' => [],
                    'stylesheets' => []];


    public function __construct(array $params = [])
    {
        if (isset($params['candles']))
        {
            $this->setCandles($params['candles']);
            unset($params['candles']);
        }
        if (isset($params['strategy']))
        {
            $this->setStrategy($params['strategy']);
            unset($params['strategy']);
        }
        $id = isset($params['id']) ?
                    $params['id'] :
                    uniqid($this->getShortClass());
        $this->setParam('id', $id);
        parent::__construct($params);
    }


    public function toJSON($options = 0)
    {
        $o = new \stdClass();
        $o->id = $this->getParam('id');
        $o->start = $this->getCandles()->getParam('start');
        $o->end = $this->getCandles()->getParam('end');
        $o->resolution = $this->getCandles()->getParam('resolution');
        $o->limit = $this->getCandles()->getParam('limit');
        $o->exchange = $this->getCandles()->getParam('exchange');
        $o->symbol = $this->getCandles()->getParam('symbol');
        //$o->refresh = function(){};
        return json_encode($o, $options);
    }


    public function handleJSONRequest(Request $request)
    {
        return $this->toJSON();
    }


    public function toHTML(string $content = '')
    {
        $this->addPageElement('scripts_top',
                    '<script> window.ESR = '.json_encode(Exchange::getESR()).'; </script>', true);

        $this->addPageElement('scripts_bottom',
                    '<script src="'.mix('/js/Chart.js').'"></script>', true);

        $this->addPageElement('scripts_top',
                    '<script> window.'.$this->getParam('id').' = '.$this->toJSON().'; </script>');

        return view('Chart', ['id' => $this->getParam('id'), 'content' => $content]);
    }


    public function getPageElements(string $element)
    {
        if (is_array($this->_page_elements[$element]))
            return join("\n", $this->_page_elements[$element]);
        return '';
    }

    public function addPageElement(string $element, string $content, bool $single_instance = false)
    {
        if (!is_array($this->_page_elements[$element]))
            return $this;
        if ($single_instance)
            foreach ($this->_page_elements[$element] as $existing_element)
                if ($content === $existing_element)
                    return $this;
        $this->_page_elements[$element][] = $content;
        return $this;
    }


    public function getStrategy()
    {
        if (!is_object($this->_strategy))
            $this->_strategy = Strategy::make();
        return $this->_strategy;
    }

    public function setStrategy(&$strategy)
    {
        $this->_strategy = $strategy;
        return $this;
    }


    public function getIndicators()
    {
        return array_merge($this->getCandles()->getIndicators(),
                            $this->getStrategy()->getIndicators());
    }


    public function getIndicatorsVisibleSorted()
    {
        $ind_sorted = [];
        $all_ind = $this->getIndicators();

        foreach ($all_ind as $ind)
        {
            $func = false;
            if (true === $ind->getParam('display.visible'))
            {
                $func = 'array_unshift';
                if ('right' === $ind->getParam('display.y_axis_pos'))
                    $func = 'array_push';
            }
            if ($func)
                $func($ind_sorted, $ind);
        }
        return $ind_sorted;
    }

}
