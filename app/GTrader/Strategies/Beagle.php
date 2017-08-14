<?php

namespace GTrader\Strategies;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GTrader\Strategy;
use GTrader\Training;
use GTrader\Evolution;
use GTrader\Evolvable;
use GTrader\Strategies\Tiktaalik;

class Beagle extends Training implements Evolution
{

    public function run()
    {
        //dump('Beagle::run()', $this->options);
    }


    public function introduce(Evolvable $organism)
    {}


    public function raiseGeneration(int $size): Evolution
    {}


    public function evaluateGeneration(): Evolution
    {}


    public function generationEvaluated(): bool
    {}


    public function killGeneration(int $num_survivors = 2): Evolution
    {}


    public function fittest(int $num = 1)
    {}


    public function numPastGenerations(): int
    {}


    public function getPreferences()
    {
        $prefs = [];
        foreach (['population', 'surviviors', 'mutation_rate'] as $item) {
            $prefs[$item] = $this->getParam($item);
        }
        return array_replace_recursive(
            parent::getPreferences(),
            $prefs
        );
    }


    public function handleStartRequest(Request $request)
    {
        //dd($request->all());

        if (!$strategy = $this->loadStrategy()) {
            Log::error('Could not load strategy');
            return response('Strategy not found', 403);
        }

        $options = $this->options ?? [];

        foreach (['population', 'surviviors', 'mutation_rate'] as $item) {
            $prefs[$item] = $options[$item] = floatval($request->$item) ?? 0;
        }

        $this->options = $options;

        Auth::user()->setPreference(
            $this->getShortClass(),
            $prefs
        )->save();

        $strategy->setParam(
            'last_training',
            array_merge($strategy->getParam('last_training', []), $options)
        )->save();

        return parent::handleStartRequest($request);
    }
}
