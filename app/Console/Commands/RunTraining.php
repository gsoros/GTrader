<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GTrader\Lock;
use GTrader\FannTraining as Training;

class RunTraining extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:run {slot} {training_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Fann Training.';

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
        $training = Training::findOrFail($this->argument('training_id'));


        $slot_lock = 'training_slot_'.$this->argument('slot');
        if (!Lock::obtain($slot_lock))
            throw new \Exception('Could not obtain slot lock for '.$this->argument('slot'));

        $training->run();

        Lock::release($slot_lock);
    }
}








