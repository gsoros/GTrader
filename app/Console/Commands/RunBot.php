<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GTrader\Bot;

class RunBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:run {bot_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a Bot.';

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
        $bot = Bot::findOrFail($this->argument('bot_id'));
        $bot->run();
    }
}
