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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    protected $casts = [
        'time' => 'int',
        'open' => 'float',
        'close' => 'float',
        'high' => 'float',
        'low' => 'float',
        'volume' => 'int',
    ];


    public function save(array $options = [])
    {

        $attributes = ['id', 'time', 'exchange_id', 'symbol_id', 'resolution',
                        'open', 'high', 'low', 'close', 'volume'];

        foreach ($this->attributes as $k => $v) {
            if (!in_array($k, $attributes)) {
                echo 'not saving attribute '.$k.' = '.$v.'<br />';
                unset($this->attributes[$k]);
            }
        }

        if (isset($this->resolution)) {
            $this->resolution = intval($this->resolution);
        }

        $query = self::select('id');

        foreach (['time', 'exchange_id', 'symbol_id', 'resolution'] as $k) {
            if (!isset($this->$k)) {
                throw new \Exception('Cannot save without '.$k);
            }
            $query->where($k, $this->$k);
        }

        if (is_object($query->first())) {
            DB::table('candles')
                ->where('id', $query->first()->id)
                ->update($this->attributes);
            return $this;
        }
        $this->insert($this->attributes, $options);
        return $this;
    }

    public function dump()
    {
        return json_encode($this->attributes);
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
