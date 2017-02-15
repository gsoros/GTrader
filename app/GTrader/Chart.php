<?php

namespace GTrader;

use Illuminate\Http\Request;
use GTrader\Exchange;
use GTrader\Strategy;
use GTrader\Indicator;

abstract class Chart extends Skeleton {
    use HasCandles, HasIndicators;

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
        $this->_indicators[] = 'this array should not be used';
        parent::__construct($params);
    }


    public function __sleep()
    {
        return ['_params', '_candles', '_strategy'];
    }
    public function __wakeup()
    {
    }


    public function handleSettingsFormRequest(Request $request)
    {
        $form = '<h2>Indicators:</h2>';
        $prev_section = null;
        foreach ($this->getIndicatorsVisibleSorted() as $ind)
        {
            if ($ind->getOwner() === $this->getCandles() && 'candles' != $prev_section)
            {
                $form .= '<h3>Candles</h3>';
                $prev_section = 'candles';
            }
            else if ($ind->getOwner() === $this->getStrategy() && 'strategy' != $prev_section)
            {
                $form .= '<h3>Strategy</h3>';
                $prev_section = 'strategy';
            }
            $form .= '<p>'.$ind->getDisplaySignature().'</p>';
        }
        return $form;
    }


    public function toJSON($options = 0)
    {
        $candles = $this->getCandles();
        $o = new \stdClass();
        $o->id = $this->getParam('id');
        //$o->start = $candles->getParam('start');
        //$o->end = $candles->getParam('end');
        //$o->limit = $candles->getParam('limit');
        $o->exchange = $candles->getParam('exchange');
        $o->symbol = $candles->getParam('symbol');
        $o->resolution = $candles->getParam('resolution');
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


    public function addIndicator($indicator, array $params = [])
    {
        if (!is_object($indicator))
            $indicator = Indicator::make($indicator, $params);
        if ($this->hasIndicator($indicator->getSignature()))
            return $this;
        $candles = $this->getCandles();
        if ($indicator->canBeOwnedBy($candles))
        {
            $candles->addIndicator($indicator);
            return $this;
        }
        $strategy = $this->getStrategy();
        if ($indicator->canBeOwnedBy($strategy))
        {
            $strategy->addIndicator($indicator);
            return $this;
        }
        return $this;
    }


    public function getIndicators()
    {
        return array_merge($this->getCandles()->getIndicators(),
                            $this->getStrategy()->getIndicators());
    }


    public function getIndicatorsVisibleSorted()
    {
        return $this->getIndicatorsFilteredSorted(
                    ['display.visible' => true],
                    ['display.y_axis_pos' => 'left', 'display.name']);
    }

}
