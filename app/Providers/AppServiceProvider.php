<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Query log
        // SET global log_output = 'FILE'; SET global general_log_file='/var/log/mysql/query.log'; SET global general_log = 0;
        if (false) { // disabled
            DB::listen(function ($query) {
                \GTrader\Log::sparse(vsprintf(
                    str_replace(['?'], ['\'%s\''], $query->sql),
                    array_map(function ($s) {
                        return 200 < strlen($s) ? substr($s, 0, 197).'...' : $s;
                    }, $query->bindings)
                ));
            });
        }

        // Save some memory
        DB::connection()->disableQueryLog();

        // Set memory limit
        ini_set('memory_limit', config('app.memory_limit', '512M'));

        Blade::if('env', function ($environment) {
            return app()->environment($environment);
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
