<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'preferences' => 'array',
    ];


    public function getPreference(string $key, $default = null)
    {
        $prefs = $this->preferences;
        if (isset($prefs[$key])) {
            if (is_array($prefs[$key]) && is_array($default)) {
                return array_replace_recursive($default, $prefs[$key]);
            }
            return $prefs[$key];
        }
        return $default;
    }


    public function setPreference(string $key, $value)
    {
        $prefs = $this->preferences;
        $prefs = is_array($prefs) ? $prefs : [];
        $this->preferences = array_replace_recursive($prefs, [$key => $value]);
        return $this;
    }

    /**
     * Get the configs for the exchanges.
     */
    public function exchangeConfigs()
    {
        return $this->hasMany('\GTrader\UserExchangeConfig');
    }


    /**
     * Get the trades of the user.
     */
    public function trades()
    {
        return $this->hasMany('\GTrader\Trade');
    }


    /**
     * Get the bots of the user.
     */
    public function bots()
    {
        return $this->hasMany('\GTrader\Bot');
    }


    /**
     * Get the Strategies of the user.
     */
    public function strategies()
    {
        return \GTrader\Strategy::getListOfUser($this->id, true);
    }

    /**
     * Get the Charts of the user.
     */
    public function charts()
    {
        $charts_db = DB::table('charts')
            ->select('name')
            ->where('user_id', $this->id)
            ->orderBy('name')
            ->get();

        $charts = [];
        foreach ($charts_db as $chart_db) {
            $charts[] = \GTrader\Chart::load($this->id, $chart_db->name);
        }
        return $charts;

    }

}
