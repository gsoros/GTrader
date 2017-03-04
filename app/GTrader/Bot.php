<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use GTrader\Exchange;
use GTrader\Series;
use GTrader\Lock;

class Bot extends Model
{
    use Skeleton, HasStrategy;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bots';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
    ];


    public function run()
    {
        // Make sure only one instance is running
        $lock = 'bot_'.$this->id;
        if (!Lock::obtain($lock))
            throw new \Exception('Could not obtain lock for '.$this->id);

        // Set up our Exchange object
        $exchange_name = Exchange::getNameById($this->exchange_id);
        $exchange = Exchange::make($exchange_name);

        // Check if we got the correct exchange
        if ($exchange->getShortClass() !== $exchange_name)
            throw new \Exception('Wanted '.$exchange_name.' got '.$exchange->getShortClass());

        // Set up our series
        $candles_limit = 200;
        $candles = new Series([
                        'exchange' => Exchange::getNameById($this->exchange_id),
                        'symbol' => Exchange::getSymbolNameById($this->symbol_id),
                        'resolution' => $this->resolution,
                        'limit' => $candles_limit]);

        $t = time();

        // Set up the strategy
        $strategy = $this->getStrategy();
        $strategy->setCandles($candles);

        // Fire signal even if previous signal was identical
        $strategy->setParam('spitfire', true);

        // Check for a signal
        $signals = $strategy->getSignals();
        $signal_times = array_keys($signals);
        $last_signal_time = array_pop($signal_times);
        $last_signal = array_pop($signals);
        $last_signal = array_merge($last_signal, ['time' => $last_signal_time]);

        // See if signal is recent enough
        if ($last_signal['time'] < $t - $this->getParam('signal_lifetime') * $this->resolution)
            return $this;

        // Looks like we have a valid signal, tell the exchange to take a position
        $exchange->takePosition($last_signal['signal']);

        echo date('Y-m-d H:i T', $last_signal['time'])."\n";
        var_export($last_signal);

        // Release our lock
        Lock::release($lock);

        return $this;
    }


    public static function getListOfUser(int $user_id)
    {
        $bots = self::where('user_id', $user_id)
                        ->orderBy('name')
                        ->get();

        return view('Bot/List', ['bots' => $bots]);
    }


    public function toHTML(string $content = null)
    {
        return view('Bot/Form', ['bot' => $this]);
    }


    public function handleSaveRequest(Request $request)
    {
        //error_log(var_export($request->all(), true));

        $ex = 'exchange_bot_'.$this->id;
        if (isset($request->$ex))
            $this->exchange_id = Exchange::getIdByName($request->$ex);

        $sy = 'symbol_bot_'.$this->id;
        if (isset($request->$sy))
            $this->symbol_id = Exchange::getSymbolIdByExchangeSymbolName(
                                        $request->$ex,
                                        $request->$sy);

        $re = 'resolution_bot_'.$this->id;
        if (isset($request->$re))
            $this->resolution = $request->$re;

        $st = 'strategy_select_bot_'.$this->id;
        if (isset($request->$st))
            if (DB::table('strategies')->where('id', $request->$st)
                                        ->where('user_id', Auth::id())
                                        ->count())
            $this->strategy_id = $request->$st;

        foreach (['name'] as $param)
            if (isset($request->$param))
                $this->$param = $request->$param;

        return $this;
    }


    /**
     * Get JSON representation of the bot.
     *
     * @param $options options for json_encode()
     * @return string JSON string
     */
    public function toJSON($options = 0)
    {
        $o = new \stdClass();
        $o->name = $this->name;
        $o->exchange = Exchange::getNameById((int)$this->exchange_id);
        $o->symbol = Exchange::getSymbolNameById((int)$this->symbol_id);
        $o->resolution = $this->resolution;
        $o->strategy_id = $this->strategy_id;
        return json_encode($o, $options);
    }


    public function getStrategy()
    {
        return Strategy::load($this->strategy_id);
    }

}







