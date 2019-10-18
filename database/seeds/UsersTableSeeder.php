<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\User;
use GTrader\Exchange;
use GTrader\Log;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (DB::table('users')->count()) {
            return;
        }

        $user_id = DB::table('users')->insertGetId([
            'name' => 'gtrader',
            'email' => 'gtrader@localhost',
            'password' => Hash::make(''),
        ]);

        $user = User::where(['id' => $user_id])->first();

        $exchange = Exchange::make('CCXT\\bitfinex2');

        $config = $user->exchangeConfigs()->firstOrNew([
            'exchange_id' => $exchange->getId(),
        ]);

        $config->options = [
            'symbols' => [
                'BTC/USD' => [
                    'resolutions' => [3600],
                ],
            ],
        ];
        $config->save();

        //Log::debug($user->id, $exchange->getId(), $config->id);
    }
}
