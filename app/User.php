<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        return isset($prefs[$key]) ? $prefs[$key] : $default;
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
        return $this->hasMany('GTrader\UserExchangeConfig');
    }


    /**
     * Get the trades of the user.
     */
    public function trades()
    {
        return $this->hasMany('GTrader\Trade');
    }


    /**
     * Get the bots of the user.
     */
    public function bots()
    {
        return $this->hasMany('GTrader\Bot');
    }
}
