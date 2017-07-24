<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\FetchCandles::class,
        Commands\RunTraining::class,
        Commands\RunTrainingManager::class,
        Commands\RunBot::class,
        Commands\RunBots::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('candles:fetch')->everyMinute()
            ->appendOutputTo(storage_path('logs/schedule.log'));
        $schedule->command('trainingManager:run')->everyMinute()
            ->appendOutputTo(storage_path('logs/trainingManager.log'));
        $schedule->command('bots:run')->everyMinute()
            ->appendOutputTo(storage_path('logs/bots.log'));

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
