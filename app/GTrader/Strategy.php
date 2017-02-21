<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use GTrader\Page;


class Strategy extends Skeleton
{
    use HasCandles, HasIndicators;


    public static function load(int $id)
    {
        $query = DB::table('strategies')
                    ->select('user_id', 'name', 'strategy')
                    ->where('id', $id)
                    ->first();
        if (!is_object($query))
            return null;
        $strategy = unserialize($query->strategy);
        $strategy->setParam('id', $id);
        $strategy->setParam('user_id', $query->user_id);
        $strategy->setParam('name', $query->name);
        return $strategy;
    }


    public function save()
    {
        if (!($id = $this->getParam('id')))
        {
            error_log('tried to save strategy without id');
            return $this;
        }
        if (!($user_id = $this->getParam('user_id')))
        {
            error_log('tried to save strategy without user_id');
            return $this;
        }

        if ('new' === $id)
        {
            $id = DB::table('strategies')
                        ->insertGetId([
                            'user_id' => $user_id,
                            'name' => $this->getParam('name'),
                            'strategy' => serialize($this)
                        ]);
            $this->setParam('id', $id);
            return $this;
        }
        $affected = DB::table('strategies')
                        ->where('id', $id)
                        ->where('user_id', $user_id)
                        ->update([
                            'name' => $this->getParam('name'),
                            'strategy' => serialize($this)
                        ]);
        return $this;
    }


    public function delete()
    {
        $affected = DB::table('strategies')
                        ->where('id', $this->getParam('id'))
                        ->delete();
        return $this;
    }


    public function toHTML(string $content = null)
    {
        return view('StrategyForm', [
                    'strategy' => $this,
                    'child_settings' => $content]);
    }


    public static function getList()
    {
        Page::add('scripts_bottom',
                    '<script src="'.mix('/js/Strategy.js').'"></script>');

        $strategies = DB::table('strategies')
                        ->select('id', 'name')
                        ->where('user_id', Auth::id())
                        ->orderBy('name')
                        ->get();

        return view('StrategyList', [
                        'available' => self::singleton()->getParam('available'),
                        'strategies' => $strategies]);
    }


    public static function getSelector(string $chart_name = null, int $selected_strategy = null)
    {
        $strategies = DB::table('strategies')
                        ->select('id', 'name')
                        ->where('user_id', Auth::id())
                        ->orderBy('name')
                        ->get();

        return view('StrategySelector', [
                        'chart_name' => $chart_name,
                        'selected_strategy' => $selected_strategy,
                        'strategies' => $strategies]);
    }


    public function handleSaveRequest(Request $request)
    {
        foreach (['name'] as $param)
            if (isset($request->$param))
                $this->setParam($param, $request->$param);

        $this->save();
        return $this->getList();
    }


    public function getSignalsIndicator()
    {
        $class = $this->getParam('signals_indicator_class');

        foreach ($this->getIndicators() as $indicator)
            if ($class === $indicator->getShortClass())
                return $indicator;

        $indicator = Indicator::make($class, ['display' => ['visible' => false]]);
        $this->addIndicator($indicator);

        return $indicator;
    }

}
