<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use GTrader\Exchange;
use GTrader\Strategy;
use GTrader\Indicator;
use GTrader\Page;

abstract class Chart
{
    use Skeleton, HasCandles, HasIndicators, HasStrategy
    {
        Skeleton::__construct as private __skeletonConstruct;
    }


    public function __construct(array $params = [])
    {
        if (isset($params['candles'])) {
            $this->setCandles($params['candles']);
            unset($params['candles']);
        }
        if (isset($params['strategy'])) {
            $this->setStrategy($params['strategy']);
            unset($params['strategy']);
        }

        if ($candles = $this->getCandles()) {
            if ($strategy = $this->getStrategy()) {
                if ($candles !== $strategy->getCandles()) {
                    $strategy->setCandles($candles);
                }
            }
        }

        $name = isset($params['name']) ?
                    $params['name'] :
                    uniqid($this->getShortClass());
        $this->setParam('name', $name);
        $this->indicators[] = 'this array should not be used';
        $this->__skeletonConstruct($params);
    }


    public function __sleep()
    {
        //error_log('Chart::__sleep()');
        if ($strategy = $this->getStrategy()) {
            if ($strategy_id = $strategy->getParam('id')) {
                $this->setParam('strategy_id', $strategy_id);
            }
        }
        return ['params', 'candles'];
    }


    public function __wakeup()
    {
        //error_log('Chart::__wakeup()');
        // we need a strategy fresh from the db on each wakeup
        if (!($strategy_id = $this->getParam('strategy_id'))) {
            return;
        }
        if (!($strategy = Strategy::load($strategy_id))) {
            return;
        }
        $this->setStrategy($strategy);
    }


    public static function load(
        int $user_id,
        string $name = null,
        string $make_class = null,
        array $params = []
    ) {
        if (!($chart = self::loadFromSession($name))) {
            if (!($chart = self::loadFromDB($user_id, $name))) {
                $chart = Chart::make($make_class);
            }
        }

        $params = array_merge(['name' => $name, 'user_id' => $user_id], $params);
        $chart->setParams($params);

        return $chart;
    }


    public static function loadFromDB(int $user_id, string $name)
    {
        $query = DB::table('charts')
                ->select('chart')
                ->where('user_id', $user_id)
                ->where('name', $name)
                ->first();

        if (is_object($query)) {
            return unserialize($query->chart);
        }
    }


    public static function loadFromSession(string $name = null)
    {
        return ($chart = session($name)) ? $chart : null;
    }


    public function save()
    {
        if (! $name = $this->getParam('name')) {
            error_log('Chart::save() called but we have no name.');
            return $this;
        }
        if (! $user_id = $this->getParam('user_id')) {
            error_log('Chart::save() called but we have no user_id.');
            return $this;
        }
        // save strategy
        if ($strategy = $this->getStrategy()) {
            if ($strategy_id = $strategy->getParam('id')) {
                $strategy->save();
                $this->setParam('strategy_id', $strategy_id);
            }
        }
        // don't save dimensions
        //$this->setParam('width', 0);
        //$this->setParam('height', 0);

        $basequery = DB::table('charts')
                        ->where('user_id', $user_id)
                        ->where('name', $name);
        $query = $basequery->select('id')->first();

        if (is_object($query)) {
            if ($id = $query->id) {
                $basequery->update(['chart' => serialize($this)]);
                return $this;
            }
        }

        DB::table('charts')->insert([ 'user_id' => $user_id,
                                        'name'  => $name,
                                        'chart' => serialize($this)]);
        return $this;
    }


    public function saveToSession()
    {
        if (! $name = $this->getParam('name')) {
            error_log('Chart::saveToSession() called but we have no name.');
            return this;
        }
        session([$name => $this]);
        return $this;
    }


    public function handleSettingsFormRequest(Request $request)
    {
        return view('ChartSettings', [
                        'indicators' => $this->getIndicatorsVisibleSorted(),
                        'available' => $this->getIndicatorsAvailable(),
                        'name' => $this->getParam('name')]);
    }


    public function handleIndicatorFormRequest(Request $request)
    {
        $indicator = $this->getIndicator($request->signature);
        return view('Indicators/'.$indicator->getShortClass(), [
                                        'name' => $this->getParam('name'),
                                        'indicator' => $indicator,
                                        'chart'     => $this]);
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$request->signature) {
            error_log('handleIndicatorNewRequest without signature');
            return $this->handleSettingsFormRequest($request);
        }

        $indicator = Indicator::make($request->signature);
        if ($this->hasIndicator($indicator->getSignature())) {
            $indicator = $this->getIndicator($indicator->getSignature());
            $indicator->setParam('display.visible', true);
        } else {
            $this->addIndicator($indicator);
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
        if ($indicator = $this->getIndicator($request->signature)) {
            $jso = json_decode($request->params);
            foreach ($indicator->getParam('indicator') as $param => $val) {
                if (isset($jso->$param)) {
                    $indicator->setParam('indicator.'.$param, $jso->$param);
                    if ($param === 'base') {
                        $indicator->setParam('depends', []);
                        $dependency = $this->getIndicator($jso->$param);
                        if (is_object($dependency)) {
                            $indicator->setParam('depends', [$dependency]);
                        }
                    }
                }
            }
            $this->unsetIndicators($indicator->getSignature());
            $indicator->setParam('display.visible', true);
            $this->addIndicator($indicator);
        }
        return $this->handleSettingsFormRequest($request);
    }


    public function getBasesAvailable(string $except_signature = null)
    {
        $bases = ['open' => 'Open',
                    'high' => 'High',
                    'low' => 'Low',
                    'close' => 'Close',
                    'volume' => 'Volume'];
        foreach ($this->getIndicatorsVisibleSorted() as $ind) {
            if (!$ind->getParam('display.top_level') &&
                $except_signature != $ind->getSignature()) {
                $bases[$ind->getSignature()] = $ind->getDisplaySignature();
            }
        }
        return $bases;
    }


    public function getIndicatorsAvailable()
    {
        $indicator = Indicator::make();
        $config = $indicator->getParam('available');
        $candles = $this->getCandles();
        $strategy = $this->getStrategy();
        $available = [];
        foreach ($config as $class => $params) {
            $exists = $this->hasIndicatorClass($class, ['display.visible' => true]);
            if (!$exists || ($exists && true === $params['allow_multiple'])) {
                $indicator = Indicator::make($class);
                if (is_object($strategy)) {
                    if ($indicator->canBeOwnedBy($strategy)) {
                        $available[$class] = $indicator->getParam('display.name');
                        continue;
                    }
                }
                if (is_object($candles)) {
                    if ($indicator->canBeOwnedBy($candles)) {
                        $available[$class] = $indicator->getParam('display.name');
                    }
                }
            }
        }
        //error_log(serialize($available));
        return $available;
    }


    /**
     * Get JSON representation of the chart.
     *
     * @param $options options for json_encode()
     * @return string JSON string
     */
    public function toJSON($options = 0)
    {
        $candles = $this->getCandles();
        $o = new \stdClass();
        $o->name = $this->getParam('name');
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


    /**
     * Get HTML representation of the chart.
     *
     * @param $content
     * @return string
     */
    public function toHTML(string $content = '')
    {
        return view('Chart', [
                        'name' => $this->getParam('name'),
                        'height' => $this->getParam('height'),
                        'content' => $content,
                        'JSON' => $this->toJSON(),
                        'disabled' => $this->getParam('disabled', []),
                        'readonly' => $this->getParam('readonly', []),
                        'chart' => $this
                        ]);
    }


    public function addPageElements()
    {
        Page::add(
            'stylesheets',
            '<link href="'.mix('/css/Chart.css').'" rel="stylesheet">'
        );
        Page::add(
            'scripts_top',
            '<script> window.ESR = '.json_encode(Exchange::getESR()).'; </script>'
        );
        //Page::add('scripts_top',
        //            '<script> window.'.$this->getParam('name').' = '.$this->toJSON().'; </script>');
        return $this;
    }


    public function addIndicator($indicator, array $params = [])
    {
        if (!is_object($indicator)) {
            $indicator = Indicator::make($indicator, $params);
        }

        if ($this->hasIndicator($indicator->getSignature())) {
            return $this;
        }

        $candles = $this->getCandles();
        if ($indicator->canBeOwnedBy($candles)) {
            $candles->addIndicator($indicator);
        } else {
            $strategy = $this->getStrategy();
            if ($indicator->canBeOwnedBy($strategy)) {
                $strategy->addIndicator($indicator);
            }
        }

        $indicator->createDependencies();
        return $this;
    }


    public function getIndicators()
    {
        $candles_ind = [];
        if ($candles = $this->getCandles()) {
            $candles_ind = $candles->getIndicators();
        }

        $strat_ind = [];
        if ($strategy = $this->getStrategy()) {
            $strat_ind = $strategy->getIndicators();
        }

        return array_merge($candles_ind, $strat_ind);
    }


    public function unsetIndicator(Indicator $indicator)
    {
        $signature = $indicator->getSignature();
        if ($this->getCandles()->hasIndicator($signature)) {
            $this->getCandles()->unsetIndicator($indicator);
        } elseif ($this->getStrategy()->hasIndicator($signature)) {
            $this->getStrategy()->unsetIndicator($indicator);
        }
        return $this;
    }

    public function getIndicatorsVisibleSorted()
    {
        $indicators = $this->getIndicatorsFilteredSorted(
            ['display.visible' => true],
            ['display.y_axis_pos' => 'left', 'display.name']
        );

        if (is_array($visible = $this->getParam('visible_indicators'))) {
            foreach ($indicators as $key => $indicator) {
                if (!in_array($indicator->getShortClass(), $visible)) {
                    unset($indicators[$key]);
                }
            }
        }
        return $indicators;
    }
}
