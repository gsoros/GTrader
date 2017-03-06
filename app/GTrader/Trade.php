<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trades';

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
     * Get the user that owns the trade.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the bot that owns the trade.
     */
    public function bot()
    {
        return $this->belongsTo('GTrader\Bot');
    }

}
