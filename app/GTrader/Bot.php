<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

/**
 * Runs a strategy on an exchange.
 */
class Bot extends Model
{
    use Skeleton, HasStrategy, Scheduled;


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


    public function __construct(array $params = [])
    {
        parent::__construct();
        $this->skeletonConstruct($params);
    }


    /**
     * Get the trades of the bot.
     * @return Trade
     */
    public function trades()
    {
        return $this->hasMany('\GTrader\Trade');
    }

    /**
     * Get the user that owns the bot.
     * @return \App\User
     */
    public function user()
    {
        return $this->belongsTo('\App\User');
    }

    /**
     * Run the bot.
     * @return $this
     */
    public function run()
    {

        // make sure we are active
        if ('active' !== $this->status) {
            throw new \Exception('run() called but bot not active: '.$this->id);
        }

        // make sure the schedule is enabled
        if (!$this->scheduleEnabled()) {
            return $this;
        }

        // Make sure only one instance is running
        $lock = 'bot_'.$this->id;
        if (!Lock::obtain($lock)) {
            throw new \Exception('Could not obtain lock for '.$this->id);
        }

        // Get the symbol's local name
        $symbol = Exchange::getSymbolNameById($this->symbol_id);

        // Set up our Exchange object
        // Tell the exchange which user's settings should be loaded
        $exchange_name = Exchange::getNameById($this->exchange_id);
        $exchange = Exchange::make($exchange_name, ['user_id' => $this->user_id]);

        // Save a record of any filled orders into local db
        $exchange->saveFilledTrades($symbol, $this->id);

        // Cancel unfilled orders
        /*
        if ($unfilled_max = intval(Arr::get($this->options, 'unfilled_max'))) {
            $exchange->cancelOpenOrders(
                $symbol,
                time() - $unfilled_max * $this->resolution
            );
        }
        */
        $exchange->cancelOpenOrders($symbol);


        // Set up our series
        $candles_limit = 200;
        $candles = new Series([
            'exchange' => $exchange->getName(),
            'symbol' => $symbol,
            'resolution' => $this->resolution,
            'limit' => $candles_limit,
        ]);

        $t = time();

        // Set up the strategy
        $strategy = $this->getStrategy();
        $strategy->setCandles($candles);

        // Check for a signal
        $signals = $strategy->getSignals();
        //Log::debug('signals:', $signals);
        $signal_times = array_keys($signals);
        $last_signal_time = array_pop($signal_times);
        if (!$last_signal = array_pop($signals)) {
            echo "No signals\n";
            Lock::release($lock);
            return $this;
        }
        $last_signal = array_merge(
            $last_signal,
            ['time' => $last_signal_time]
        );
        //Log::debug('last_signal:', $last_signal);

        // See if signal is recent enough
        if ($last_signal['time'] < $t - $this->getParam('signal_lifetime') * $this->resolution) {
            return $this;
        }

        // Looks like we have a valid signal
        echo 'Bot ['.$this->name.'] taking '.$last_signal['signal'].' position at '.$last_signal['price']."\n";
        // Tell the exchange to take the position
        $exchange->takePosition(
            $symbol,
            $last_signal,
            $this->id,
        );

        // Release our lock
        Lock::release($lock);

        return $this;
    }


    public static function getListOfUser(int $user_id, bool $return_array = false)
    {
        $bots = self::where('user_id', $user_id)
            ->orderBy('name')
            ->get();

        return $return_array ? $bots : view('Bot/List', ['bots' => $bots]);
    }


    public function toHTML(string $content = null)
    {
        return view('Bot/Form', ['bot' => $this]);
    }


    public function handleSaveRequest(Request $request)
    {
        //Log::debug($request->all());

        $ex = 'exchange_bot_'.$this->id;
        if (isset($request->$ex)) {
            $this->exchange_id = Exchange::getOrAddIdByName($request->$ex);
        }

        $sy = 'symbol_bot_'.$this->id;
        if (isset($request->$sy)) {
            $this->symbol_id = Exchange::getSymbolIdByExchangeSymbolName(
                $request->$ex,
                $request->$sy
            );
        }

        $re = 'resolution_bot_'.$this->id;
        if (isset($request->$re)) {
            $this->resolution = $request->$re;
        }

        $st = 'strategy_select_bot_'.$this->id;
        if (isset($request->$st)) {
            if (DB::table('strategies')->where('id', $request->$st)
                ->where('user_id', Auth::id())
                ->count()) {
                $this->strategy_id = $request->$st;
            }
        }

        foreach (['name'] as $param) {
            if (isset($request->$param)) {
                $this->$param = $request->$param;
            }
        }

        if (isset($request->status)) {
            if (in_array($request->status, ['active', 'disabled'])) {
                $this->status = $request->status;
                if ('active' === $this->status) {
                    if (!$this->canBeActive()) {
                        // TODO send error msg to UI
                        $this->status = 'disabled';
                        $exchange_name = Exchange::getNameById($this->exchange_id);
                        Log::error('Tried to activate bot ID '.$this->id.' for '.$exchange_name.
                                    ' but there are other active bots on this exchange.');
                    }
                }
            }
        }

        $options = $this->options;
        foreach ($this->getParam('user_options') as $option => $default) {
            $options[$option] = isset($request->$option) ?
                $request->$option :
                $default;
        }
        $this->options = $options;

        return $this;
    }


    public function canBeActive()
    {
        $active_bots = Bot::where('id', '<>', $this->id)
            ->where('status', 'active')
            ->where('exchange_id', $this->exchange_id)
            ->count();
        return $active_bots ? false : true;
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

    public function getExchangeName()
    {
        return Exchange::getLongNameById($this->exchange_id);
    }
}
