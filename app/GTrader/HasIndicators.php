<?php

namespace GTrader;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait HasIndicators
{
    use Visualizable {
        visualize as __Visualizable__visualize;
    }

    public $indicators = [];


    public function __clone()
    {
        $inds = $this->getIndicators();
        $this->unsetIndicators();
        foreach ($inds as $ind) {
            //if ('Vol' == Indicator::decodeSignature($ind->getSignature())['class']) dump('Cloning vol');
            $new_ind = clone $ind;
            $this->addIndicator($new_ind);
            $new_ind->addRef('root');
            $new_ind->setParams($ind->getParams());
        }
    }


    public function kill()
    {
        //Log::debug('.');
        foreach ($this->getIndicators() as $i) {
            if ($this === $i->getOwner()) {
                $i->kill();
            }
        }
        $this->unsetIndicators();
        return $this;
    }

    public function getIndicatorOwner()
    {
        return $this;
    }


    public function addIndicator(
        $indicator,
        array $params = [],
        array $params_if_new = []
    ) {
        //dump('addIndicator() i: '.json_encode($indicator).' p: '.json_encode($params).
        //  ' pin: '.json_encode($params_if_new));

        if (!is_object($indicator)) {
            if (!$indicator) {
                Log::error('tried to make ind without class');
                return null;
            }
            $ind_str = $indicator;
            if (!($indicator = Indicator::make(
                $indicator,
                ['indicator' => $params]
            ))) {
                Log::error('could not make', $ind_str);
                //dd(debug_backtrace());
                return null;
            }
        }

        $owner = $this->getIndicatorOwner();

        if (!$indicator->canBeOwnedBy($owner)) {
            throw new \Exception($indicator->getShortClass().' cannot be owned by '.$owner->getShortClass());
            return null;
        }
        $indicator->setOwner($owner);
        $indicator->init();
        if ($owner->hasIndicator($sig = $indicator->getSignature())) {
            //Log::debug('owner has', $owner->oid(), $sig);
            $existing = $owner->getIndicator($sig);
            //$existing->setParams($indicator->getParams());
            return $existing;
        }
        $class = $indicator->getShortClass();
        if (!config('GTrader.Indicators.available.'.$class.'.allow_multiple', false) &&
            $owner->hasIndicatorClass($class)) {
            $existing = $owner->getFirstIndicatorByClass($class);
            $existing->setParams($indicator->getParams());
            return $existing;
        }
        $indicator->setParams($params_if_new);
        //Log::debug('Adding indicator', substr($indicator->getSignature(), 0, 80));
        $owner->indicators[] = $indicator;
        $indicator->createDependencies();
        //Log::debug('Added '.$indicator->oid().' to '.$owner->oid());
        return $indicator;
    }


    public function addIndicatorBySignature(
        string $signature,
        array $params = [],
        array $params_if_new = []
    ) {
        $class = Indicator::getClassFromSignature($signature);
        //dump('addIndicatorBySignature() '.$class);
        $sig_params = Indicator::getParamsFromSignature($signature);
        $i = $this->addIndicator($class, array_replace_recursive($sig_params, $params), $params_if_new);

        return $i;
    }


    public function getIndicator(string $sig)
    {
        foreach ($this->getIndicators() as $existing_ind) {
            if (Indicator::signatureSame($sig, $existing_ind->getSignature())) {
                return $existing_ind;
            }
        }
        return null;
    }


    public function getOrAddIndicator(
        $signature,
        array $params = [],
        array $params_if_new = []
    ) {
        // dump('addIndicator() S: '.json_encode($signature).' p: '.json_encode($params).
        //     ' pin: '.json_encode($params_if_new));

        if (!is_string($signature)) {
            $signature = json_encode($signature, true);
            //Log::warning('warning, converted to string: '.$signature);
        }
        if (!strlen($signature)) {
            return null;
        }
        if (in_array($signature, ['open', 'high', 'low', 'close', 'volume'])) {
            return null;
        }
        if (!($indicator = $this->getIndicator($signature))) {
            if (!($indicator = $this->addIndicatorBySignature($signature, $params, $params_if_new))) {
                return null;
            }
        }
        return $indicator;
    }


    public function getFirstIndicatorByClass(string $class)
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getShortClass() === $class) {
                return $indicator;
            }
        }
        return null;
    }


    public function hasIndicator(string $signature)
    {
        return $this->getIndicator($signature) ? true : false;
    }


    public function hasIndicatorClass(string $class, array $filters = [])
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getShortClass() === $class) {
                if (!count($filters)) {
                    return true;
                }
                foreach ($filters as $conf => $val) {
                    if ($indicator->getParam($conf) != $val) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }


    public function getIndicatorLastValue(string $class, array $params = [], bool $force_rerun = false)
    {
        $indicator = Indicator::make($class, $params);
        if (!$this->hasIndicatorClass($class, $params)) {
            $this->addIndicator($indicator);
        }
        $sig = $indicator->getSignature();
        if (!($indicator = $this->getIndicator($sig))) {
            Log::error($sig.' not found.');
            return 0;
        }
        return $indicator->getLastValue($force_rerun);
    }


    public function unsetIndicator(Indicator $indicator)
    {
        if (!$owner = $this->getIndicatorOwner()) {
            Log::error('no owner');
            return $this;
        }
        $sig = $indicator->getSignature();
        $target = $key = null;
        foreach ($owner->indicators as $key => $existing) {
            if ($indicator === $existing) {
                $target = $existing;
                break;
            }
        }
        if (is_null($target)) {
            //Log::error('not found', substr($sig, 0, 50));
            return $this;
        }
        // if (0 < $target->refCount() && ['root'] !== array_merge($target->getRefs())) {
        //     Log::error('warning: refcount is non-zero for sig: '.$sig.
        //         ' refs: '.json_encode($target->getRefs()));
        // }
        // Log::error($target->oid());
        if ($owner === $target->getOwner()) {
            $target->unsetOwner();
            $target->kill();
        }
        unset($owner->indicators[$key]);
        //Log::debug('Removed '.$indicator->oid().' from '.$owner->oid());
        Event::dispatch($indicator, 'indicator.delete', ['signature' => $sig]);
        return $this;
    }


    public function unsetIndicatorBySig(string $sig)
    {
        foreach ($this->getIndicators() as $existing_ind) {
            if ($sig === $existing_ind->getSignature()) {
                $this->unsetIndicator($existing_ind);
            }
        }
        return $this;
    }


    public function unsetIndicators()
    {
        $this->getIndicatorOwner()->indicators = [];
        return $this;
    }


    public function killIndicators()
    {
        foreach ($this->getIndicators() as $ind) {
            //dump('HasIndicators::killIndicators() '.$ind->oid());
            $ind->kill();
        }
        $this->unsetIndicators();
        return $this;
    }


    public function getIndicators()
    {
        return $this->getIndicatorOwner()->indicators;
    }


    /**
     * Get indicators, filtered, sorted
     *
     * @param  array  $filters    e.g. ['display.visible' => true, ['immutable', false], ['class', 'not', 'Ema']]
     * @param  array  $sort       e.g. ['display.y-axis' => 'left', 'display.name']
     * @return array
     */
    public function getIndicatorsFilteredSorted(array $filters = [], array $sort = [])
    {
        return $this->sortIndicators(
            $this->filterIndicators($this->getIndicators(), $filters),
            $sort
        );
    }

    /**
     * Filters an array of indicators
     * @param  array  $indicators
     * @param  array  $filters    e.g. ['display.visible' => true, ['immutable', false], ['class', 'not', 'Ema']]
     * @return array
     */
    protected function filterIndicators(array $indicators = [], array $filters = [])
    {
        if (!count($filters)) {
            return $indicators;
        }
        foreach ($indicators as $ind_key => $ind_obj) {
            foreach ($filters as $filter_key => $filter_val) {
                $condition = '==';
                if  (is_array($filter_val)) {
                    //dump($filter_val);
                    //dump('HasIndicators::filterIndicators filter is array:', $filter_val);

                    if (isset($filter_val[0]) && isset($filter_val[1])) {
                        if (isset($filter_val[2])) {
                            $filter_key = $filter_val[0];
                            $condition = $filter_val[1];
                            $filter_val = $filter_val[2];
                        }
                        else {
                            $condition = $filter_val[0];
                            $filter_val = $filter_val[1];
                        }

                    }
                }
                //dump($filter_key, $condition, $filter_val);
                if ('class' === $filter_key) {
                    if (!Util::conditionMet(
                        $filter_val,
                        $condition,
                        $ind_obj->getShortClass())
                    ) {
                        //Log::debug($filter_val.' '.$condition.' '.$ind_obj->getShortClass().' is false');
                        unset($indicators[$ind_key]);
                        break;
                    }   //else Log::debug($filter_val.' '.$condition.' '.$ind_obj->getShortClass().' is true');
                    continue;
                }
                if (!Util::conditionMet(
                    $ind_obj->getParam($filter_key),
                    $condition,
                    $filter_val
                )) {
                    unset($indicators[$ind_key]);
                    break;
                }
            }
        }
        return $indicators;
    }


    /**
     * Sorts an array of indicators
     * @param  array  $indicators
     * @param  array  $sort       e.g. ['display.y-axis' => 'left', 'display.name']
     * @return array
     */
    protected function sortIndicators(array $indicators = [], array $sort = [])
    {
        if (!count($sort)) {
            return $indicators;
        }

        $get_value = function($indicator, $key) {
            if ('signature' === $key) {
                return $indicator->getSignature();
            }
            return $indicator->getParam($key);
        };

        foreach (array_reverse($sort) as $sort_key => $sort_val) {
            if (is_string($sort_key)) {
                usort(
                    $indicators,
                    function (Indicator $ind1, Indicator $ind2) use (
                        $sort_key, $sort_val, $get_value
                    ) {
                        $val1 = $get_value($ind1, $sort_key);
                        $val2 = $get_value($ind2, $sort_key);
                        if ($val1 === $sort_val) {
                            return ($val2 === $sort_val) ? 0 : -1;
                        }
                        if ($val2 === $sort_val) {
                            return 1;
                        }
                        return 0;
                    }
                );
            } else {
                usort(
                    $indicators,
                    function (Indicator $ind1, Indicator $ind2) use (
                        $sort_val, $get_value
                    ) {
                        $val1 = $get_value($ind1, $sort_val);
                        $val2 = $get_value($ind2, $sort_val);
                        if (is_numeric($val1) && is_numeric($val2)) {
                            $val1 = floatval($val1);
                            $val2 = floatval($val2);
                            return $val1 === $val2 ? 0 : ($val1 > $val2 ? 1 : -1);
                        }
                        return strcmp(strval($val1), strval($val2));
                    }
                );
            }
        }
        return $indicators;
    }


    public function calculateIndicators()
    {
        foreach ($this->getIndicators() as $indicator) {
            //dump('HasInd::calculateIndicators() '.$indicator->oid());
            $indicator->checkAndRun();
        }
        return $this;
    }


    public function dumpIndicators()
    {
        $dump = '';
        foreach ($this->getIndicators() as $indicator) {
            //$dump .= var_export($indicator->getParams(), true)."\n";
            $dump .= $indicator->getSignature()."\n";
        }
        return $dump;
    }


    public function getAvailableIndicators()
    {
        if ($cache_enabled = method_exists($this, 'cached')) {
            if ($available = $this->cached('indicators_available')) {
                return $available;
            }
        }
        $available = [];
        if (!is_object($owner = $this->getIndicatorOwner())) {
            return $available;
        }
        foreach (config('GTrader.Indicators.available', []) as $class => $params) {
            $exists = $owner->hasIndicatorClass($class, ['display.visible' => true]);
            if (!$exists || ($exists && true === $params['allow_multiple'])) {
                if (!$indicator = Indicator::make($class, ['temporary' => true])) {
                    Log::error('Could not make '.$class);
                    continue;
                }
                if ($indicator->canBeOwnedBy($owner)) {
                    //$available[$class] = $indicator->getParam('display.name');
                    $available[$class] = [
                        'name' => $indicator->getParam('display.name'),
                        'description' => $indicator->getParam('display.description'),
                    ];
                }
                $indicator->kill();
                unset($indicator);
            }
        }
        asort($available);
        if ($cache_enabled) {
            $this->cache('indicators_available', $available);
        }
        return $available;
    }


    protected function formatFromRequest(Request $request = null)
    {
        $format = 'long';
        if (is_object($request)) {
            if (isset($request->width)) {
                if (500 > intval($request->width)) {
                    $format = 'short';
                }
            }
        }
        return $format;
    }


    public function viewIndicatorsList(Request $request = null, array $options = [])
    {
        $format = $this->formatFromRequest($request);
        return view(
            'Indicators/List',
            [
                'owner' => $this,
                'indicators' => $this->getIndicatorsVisibleSorted(),
                'available' => $this->getAvailableIndicators(),
                'name' => $this->getParam('name'),
                'format' => $format,
            ]
        );
    }


    public function handleIndicatorFormRequest(Request $request)
    {
        $pass_params = [];
        foreach ([
            'name',
            'owner_class',
            'owner_id',
            'target_element',
            'mutability',
            'disabled',
        ] as $val) {
            if (isset($request[$val])) {
                $pass_params[$val] = $request[$val];
            }
        }
        $sig = urldecode($request->signature);
        if (! $indicator = $this->getIndicator($sig)) {
            Log::error('Could not find indicator', $sig);
        }
        return $indicator->getForm($pass_params);
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$sig = urldecode($request->signature)) {
            Log::error('No signature');
            return $this->viewIndicatorsList($request);
        }
        if ($indicator = $this->addIndicatorBySignature($sig)) {
            $indicator->visible(true);
            $indicator->addRef('root');
        }
        return $this->viewIndicatorsList($request);
    }



    public function handleIndicatorDeleteRequest(Request $request)
    {
        $sig = urldecode($request->signature);
        //Log::debug($sig);
        $indicator = $this->getIndicator($sig);
        $this->updateReferences();
        if ($indicator->refCount()) {
            //Log::debug('^^^^ has refCount, hiding');
            $indicator->visible(false);
        } else {
            $this->unsetIndicatorBySig($sig);
        }
        return $this->viewIndicatorsList($request);
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        //dump($request->all());
        $sig = urldecode($request->signature);
        if (! $indicator = $this->getIndicator($sig)) {
            Log::error('cannot find indicator', $sig);
            return $this->viewIndicatorsList($request);
        }
        $indicator = clone $indicator;
        $sig = $indicator->getSignature();
        try {
            $params = json_decode($request->params, true);
            $mutable = isset($request->mutable)
                ? json_decode($request->mutable, true)
                : [];
            $display = isset($request->display)
                ? json_decode($request->display, true)
                : [];
        } catch (\Exception $e) {
            Log::error('cannot decode json', $request->params, $request->mutable, $request->display);
            return $this->viewIndicatorsList($request);
        }
        $suffix = '';
        if (isset($request->suffix)) {
            if ($request->suffix) {
                $suffix = $request->suffix;
            }
        }
        $indicator->update($params, $suffix);
        $indicator->init();
        $this->unsetIndicatorBySig($sig);
        if (!$indicator = $this->addIndicator($indicator)) {
            Log::error('could not addIndicator');
            return $this->viewIndicatorsList($request);
        }
        $indicator->visible(true);
        $indicator->addRef('root');
        if (method_exists($indicator, 'createDependencies')) {
            $indicator->createDependencies();
        }
        $indicator->mutable($mutable);
        $indicator->setParam('display.outputs', $display);
        return $this->viewIndicatorsList($request);
    }


    public function handleIndicatorToggleMutableRequest(Request $request)
    {
        //dump($request->all());
        $sig = urldecode($request->signature);
        if (! $indicator = $this->getIndicator($sig)) {
            Log::error('cannot find indicator', $sig);
            return $this->viewIndicatorsList($request);
        }
        $mutable = boolval($request->mutable ?? false);
        foreach ($indicator->getParam('adjustable', []) as $key => $param) {
            $indicator->mutable($key, $mutable);
        }
        return $this->viewIndicatorsList($request);
    }


    public function getAvailableSources(
        $except_signatures = null,
        array $sources = [],
        array $filters = [],
        array $disabled = [],
        int $max_nesting = 10
    ):array {

        if (!is_array($except_signatures)) {
            $except_signatures = [$except_signatures];
        }
        foreach (
            $this->getIndicatorsFilteredSorted($filters, ['display.name']) as $ind
            ) {
            if ($ind->getParam('display.top_level')) {
                continue;
            }
            foreach ($except_signatures as $sig) {
                if (!$sig) {
                    continue;
                }
                if (Indicator::signatureSame($sig, $ind->getSignature())) {
                    continue 2;
                }
                if (method_exists($ind, 'getInputs')) {
                    if (in_array($sig, $ind->getInputs())) {
                        continue 2;
                    }
                }
                if (method_exists($ind, 'inputFrom')) {
                    if ($ind->inputFrom($sig)) {
                        continue 2;
                    }
                }
            }
            if (in_array('outputs', $disabled)) {
                $sources[$ind->getSignature()] = $ind->getDisplaySignature();
                continue;
            }
            if ($ind->nesting() > $max_nesting) {
                //Log::debug($ind->nesting().' > '.$max_nesting);
                continue;
            }
            $outputs = $ind->getOutputs();
            $output_count = count($outputs);
            $disp_sig =  $ind->getDisplaySignature();
            foreach ($outputs as $output) {
                $sig = $ind->getSignature($output);
                $label = (1 < $output_count) ? $disp_sig.' => '.ucfirst($output) : $disp_sig;
                $sources[$sig] = $label;
            }
        }
        asort($sources);
        return $sources;
    }


    public function indicatorOutputDependsOn(string $signature, $target_sigs = ['open'])
    {
        if (!is_array($target_sigs)) {
            $target_sigs = [$target_sigs];
        }
        if (in_array($signature, $target_sigs)) {
            return true;
        }
        if (!($indicator = $this->getOrAddIndicator($signature))) {
            return false;
        }
        $o = Indicator::getOutputFromSignature($signature);
        return $indicator->outputDependsOn($target_sigs, $o);
    }


    public function getFirstIndicatorOutput(string $output)
    {
        if ($cache_enabled = method_exists($this, 'cached')) {
            if ($f = $this->cached('first_indicator_output_'.$output)) {
                return $f;
            }
        }
        $indicator = $class = null;
        $outputs = [];
        if (in_array($output, ['open', 'high', 'low', 'close'])) {
            $class = 'Ohlc';
        } elseif ('volume' === $output) {
            $class = 'Vol';
        } else {
            return $output;
        }
        $ret = null;
        if ($indicator = $this->getFirstIndicatorByClass($class)) {
            $outputs = $indicator->getOutputs();
            //dump('have it: '.$indicator->oid());
        }
        if (!$indicator || !in_array($output, $outputs)) {
            //dump('making new:', in_array($output, $outputs), $output, $outputs);
            if (!$indicator = $this->getOrAddIndicator($class)) {
                $ret = $output;
            }
        }
        if ($indicator) {
            $ret = in_array($output, $indicator->getOutputs()) ?
                $indicator->getSignature($output) : $output;
        }
        if ($cache_enabled) {
            $this->cache('first_indicator_output_'.$output, $ret);
        }
        //dump($this->oid().' HasInd::getFirstIndicatorOutput('.$output.') : '.$ret);
        return $ret;
    }


    public function updateReferences()
    {
        foreach ($this->getIndicators() as $ind) {
            $ind->cleanRefs(['root']);
        }
        foreach ($this->getIndicators() as $ind) {
            $ind->updateReferences(uniqid());
        }
        return $this;
    }


    public function purgeIndicators(array $options = [])
    {
        $inds = $this->getIndicators();
        $loop = 0;
        while ($loop < 100) {
            $this->updateReferences();
            $removed = 0;
            foreach ($inds as $key => $ind) {
                //Log::debug('Checking to remove '.$ind->oid().' from '.$this->oid());
                if (
                        (
                            in_array('root', $options) ||
                            !$ind->hasRefRecursive('root') ||
                            (['root'] == array_merge($ind->getRefs())) // renumber keys
                        ) &&
                        (
                            in_array('visible', $options) ||
                            !$ind->visible()
                        )
                    ) {
                    $this->unsetIndicator($ind);
                    unset($inds[$key]);
                    $removed++;
                    //if ('Balance' == $ind->getShortClass())
                    //Log::debug('Removed '.$ind->oid().' from '.$this->oid());
                }
            }
            if (!$removed) {
                return $this;
            }
            $loop++;
        }
        //Log::debug('We had '.count($inds).', removed '.$removed.', left '.count($this->getIndicators()));
        return $this;
    }


    public function visualize(int $depth = 100)
    {
        //dump($this->oid().' HasIndicators::visualize depth: '.$depth);
        $this->__Visualizable__visualize($depth);
        if (!$depth--) {
            return $this;
        }
        foreach ($this->getIndicators() as $node) {
            if (!$this->visNodeExists($node)) {
                if (method_exists($node, 'visualize')) {
                    $node->visualize($depth);
                }
            }
            $this->visAddEdge($this, $node, [
                'title' => $this->getShortClass().' has indicator '.$node->getShortClass(),
                'arrows' => '',
                'color' => '#ffed00',
            ]);
        }

        return $this;
    }
}
