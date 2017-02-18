<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Candle extends Model
{
    /**
     * Candles should not be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    public function save(array $options = [])
    {

        $attributes = ['id', 'time', 'exchange_id', 'symbol_id', 'resolution',
                        'open', 'high', 'low', 'close', 'volume'];

        foreach ($this->attributes as $k => $v)
        {
            if (!in_array($k, $attributes))
            {
                echo 'not saving attribute '.$k.' = '.$v.'<br />';
                unset($this->attributes[$k]);
            }
        }

        if (isset($this->resolution))
            $this->resolution = intval($this->resolution);

        $q = self::select('id');

        foreach (['time', 'exchange_id', 'symbol_id', 'resolution'] as $k)
        {
            if (!isset($this->$k))
                throw new \Exception('Cannot save without '.$k);
            $q->where($k, $this->$k);
        }

        if (is_object($q->first()))
        {
            DB::table('candles')
                ->where('id', $q->first()->id)
                ->update($this->attributes);
        }
        else $this->insert($this->attributes, $options);

        return $this;
    }


    /*
    * Perform a heikin ashi calculation
    * @param candle Candle
    * @param prev_candle Candle
    * @return Candle
    * retains any additional attributes in $candle */
    public static function heikinashi(Candle $candle, Candle $prev_candle = null)
    {
        //dump($candle); dump($prev_candle); exit();
        if (!is_object($prev_candle)) return $candle;
        if (!$prev_candle->open || !$prev_candle->high ||
              !$prev_candle->low || !$prev_candle->close) return $candle;

        $candle->attributes = array_merge($candle->attributes, array(
            'open' => ($prev_candle->open + $prev_candle->close) / 2,
            'high' => max($candle->open, $candle->high, $candle->close),
            'low' => min($candle->low, $candle->open, $candle->close),
            'close' => ($candle->open + $candle->high + $candle->low + $candle->close) / 4
            ));
        return $candle;
    }


    public function max()
    {
        return max($this->open, $this->high, $this->low, $this->close);
    }


    public function min()
    {
        return min($this->open, $this->high, $this->low, $this->close);
    }
}
