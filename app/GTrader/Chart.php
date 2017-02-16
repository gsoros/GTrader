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

        $candles = $this->getCandles();
        if ($candles !== $this->getStrategy()->getCandles())
            $this->getStrategy()->setCandles($candles);

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
        return view('ChartSettings', [
                        'indicators' => $this->getIndicatorsVisibleSorted(),
                        'available' => $this->getIndicatorsAvailable(),
                        'id' => $this->getParam('id')]);
    }


    public function handleIndicatorFormRequest(Request $request)
    {
        $indicator = $this->getIndicator($request->signature);
        return view('Indicators/'.$indicator->getShortClass(), [
                                        'id' => $this->getParam('id'),
                                        'indicator' => $indicator,
                                        'chart'     => $this]);
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$request->signature)
        {
            error_log('handleIndicatorNewRequest without signature');
        }
        else
        {
            $indicator = Indicator::make($request->signature);
            if ($this->hasIndicator($indicator->getSignature()))
            {
                $indicator = $this->getIndicator($indicator->getSignature());
                $indicator->setParam('display.visible', true);
            }
            else
            {
                $this->addIndicator($indicator);
            }
        }
        return $this->handleSettingsFormRequest($request);
    }


    public function handleIndicatorDeleteRequest(Request $request)
    {
        $indicator = $this->getIndicator($request->signature);
        $indicator->getOwner()->unsetIndicators($indicator->getSignature());
        return $this->handleSettingsFormRequest($request);
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        if ($indicator = $this->getIndicator($request->signature))
        {
            $jso = json_decode($request->params);
            foreach ($indicator->getParam('indicator') as $param => $val)
                if (isset($jso->$param))
                {
                    $indicator->setParam('indicator.'.$param, $jso->$param);
                    if ($param === 'price')
                    {   error_log('handleIndicatorSaveRequest price: '.$val.' -> '.$jso->$param);
                        $indicator->setParam('depends', []);
                        $dependency = $this->getIndicator($jso->$param);
                        if (is_object($dependency))
                            $indicator->setParam('depends', [$dependency]);
                    }
                }
            $this->unsetIndicators($indicator->getSignature());
            $indicator->setParam('display.visible', true);
            $this->addIndicator($indicator);
        }
        return $this->handleSettingsFormRequest($request);
    }


    public function getPricesAvailable(string $except_signature = null)
    {
        $prices = ['open' => 'Open',
                    'high' => 'High',
                    'low' => 'Low',
                    'close' => 'Close',
                    'volume' => 'Volume'];
        foreach ($this->getIndicatorsVisibleSorted() as $ind)
            if ($except_signature != $ind->getSignature())
                $prices[$ind->getSignature()] = $ind->getParam('display.name');
        return $prices;
    }


    public function getIndicatorsAvailable()
    {
        $indicator = Indicator::make();
        $config = $indicator->getParam('available');
        $available = [];
        foreach ($config as $class => $params)
        {
            $exists = $this->hasIndicatorClass($class, ['display.visible' => true]);
            if (!$exists || ($exists && true === $params['allow_multiple']))
            {
                $indicator = Indicator::make($class);
                $available[$class] = $indicator->getParam('display.name');
            }
        }
        error_log(serialize($available));
        return $available;
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
        $this->addPageElement('stylesheets',
                    '<link href="'.mix('/css/Chart.css').'" rel="stylesheet">', true);

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
            $candles->addIndicator($indicator);
        else
        {
            $strategy = $this->getStrategy();
            if ($indicator->canBeOwnedBy($strategy))
                $strategy->addIndicator($indicator);
        }

        $indicator->createDependencies();
        return $this;
    }


    public function getIndicators()
    {
        return array_merge($this->getCandles()->getIndicators(),
                            $this->getStrategy()->getIndicators());
    }


    public function unsetIndicator(Indicator $indicator)
    {
        $signature = $indicator->getSignature();
        if ($this->getCandles()->hasIndicator($signature))
            $this->getCandles()->unsetIndicator($indicator);
        else if ($this->getStrategy()->hasIndicator($signature))
            $this->getStrategy()->unsetIndicator($indicator);
        return $this;
    }

    public function getIndicatorsVisibleSorted()
    {
        return $this->getIndicatorsFilteredSorted(
                    ['display.visible' => true],
                    ['display.y_axis_pos' => 'left', 'display.name']);
    }

}
