<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GTrader\Lock;
use GTrader\Bot;

class RunBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Bots.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $bots = Bot::where('status', 'active');

        if (!count($bots)) {
            return null;
        }

        $lock = 'bots_run';
        if (!Lock::obtain($lock)) {
            throw new \Exception('Could not obtain lock');
        }

        foreach ($bots as $bot) {
            $bot->run();
        }

        Lock::release($lock);
    }
}
