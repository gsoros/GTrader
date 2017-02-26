<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GTrader\TrainingManager;

class RunTrainingManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trainingManager:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Training Manager.';

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
        $tm = new TrainingManager();
        $tm->run();
    }
}
