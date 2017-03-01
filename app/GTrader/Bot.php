<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;



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

        return view('BotList', ['bots' => $bots]);
    }



}







