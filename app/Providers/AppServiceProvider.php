<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function ($query) {
            
            $replace = function ($sql, $bindings)
            {
                $needle = '?';
                foreach ($bindings as $replace)
                {
                    $pos = strpos($sql, $needle);
                    if ($pos !== false)
                    {
                        $sql = substr_replace($sql, "'".$replace."'", $pos, strlen($needle));
                    }
                }
                return $sql;
            };
            $sql = $replace($query->sql, $query->bindings);
            //dump($sql);

        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
