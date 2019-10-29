<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GTrader\Aggregator;

class FetchCandles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candles:fetch
                           {--e|exchange= : fetch candlestick data from a single exchange}
                           {--s|symbol= : fetch a single symbol}
                           {--r|resolution= : fetch a single resolution}
                           {--d|direction= : direction: left|rev | right|fwd | both}
                           ';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads candlestick data from the exchanges.';

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
        //dd($this->options());
        $a = new Aggregator();
        $a->aggregate([
            'exchange' => $this->option('exchange'),
            'symbol' => $this->option('symbol'),
            'resolution' => $this->option('resolution'),
            'direction' => $this->option('direction') ?? 'both',
        ]);
    }
}
