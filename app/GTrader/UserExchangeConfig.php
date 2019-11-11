<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use GTrader\HasStatCache;

class UserExchangeConfig extends Model
{
    use HasStatCache;

    protected static $stat_cache = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_exchanges';

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

    /**
     * Get the user that owns the config.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
