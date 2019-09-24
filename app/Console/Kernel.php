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
        Commands\DeleteOldCandles::class,
        Commands\RunTraining::class,
        Commands\RunTrainingManager::class,
        Commands\RunBot::class,
        Commands\RunBots::class,
        Commands\Debug::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('candles:fetch')
            ->cron('*/'.config('GTrader.Exchange.schedule_frequency', 1).' * * * *')
            ->appendOutputTo(storage_path('logs/schedule.log'));

        $schedule->command('bots:run')
            ->cron('*/'.config('GTrader.Bot.schedule_frequency', 1).' * * * *')
            ->appendOutputTo(storage_path('logs/bots.log'));

        $schedule->command('trainingManager:run')
            ->cron('*/'.config('GTrader.TrainingManager.schedule_frequency', 1).' * * * *')
            ->appendOutputTo(storage_path('logs/trainingManager.log'));
    }


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
