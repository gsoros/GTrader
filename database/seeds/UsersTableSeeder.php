<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        DB::table('users')->insert([
            'name' => 'gtrader',
            'email' => 'gtrader@localhost',
            'password' => Hash::make('gtrader'),
        ]);
    }
}
