<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use GTrader\Exchange;


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

}







