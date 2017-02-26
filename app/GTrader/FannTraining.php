<?php

namespace GTrader;

//use GTrader\Strategies\Fann as FannStrategy;
use Illuminate\Database\Eloquent\Model;
use GTrader\Lock;
use GTrader\Exchange;
use GTrader\Series;
use GTrader\Strategy;


class FannTraining extends Model
{
    use HasParams, HasStrategy;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fann_training';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    public function run()
    {
        echo 'FannTraining::run() ID:'.$this->id."\n";

        $training_lock = 'training_'.$this->id;
        if (!Lock::obtain($training_lock))
            throw new \Exception('Could not obtain training lock for '.$this->id);

        $candles = new Series([
                    'exchange' => Exchange::getNameById($this->exchange_id),
                    'symbol' => Exchange::getSymbolNameById($this->symbol_id),
                    'resolution' => $this->resolution,
                    'start' => $this->range_start,
                    'end' => $this->range_end,
                    'limit' => 0]);

        //echo 'Candles: '.$candles->size()."\n";

        $strategy = Strategy::load($this->strategy_id);
        $strategy->setCandles($candles);

        $statusfile = $strategy->getParam('path').DIRECTORY_SEPARATOR.$this->strategy_id.'.status';

        $epochs = 0;
        $balance_max = $strategy->getLastBalance(true);

        while ($this->shouldRun())
        {
            $epochs++;
            $strategy->train(1);
            $balance = $strategy->getLastBalance(true);
            if ($balance > $balance_max)
            {
                $strategy->saveFann();
                $balance_max = $balance;
            }
            $this->writeStatus($statusfile,
                        'Epoch: '.$epochs.
                        ' Balance: '.$balance.
                        ' Signals: '.$strategy->getNumSignals(true));

        }

        Lock::release($training_lock);
    }


    protected function shouldRun()
    {
        //return false;
        // check db if we have been stopped or deleted
        try
        {
            self::where('id', $this->id)
                    ->where('status', 'training')
                    ->firstOrFail();
        }
        catch (\Exception $e)
        {
            echo "Training stopped\n";
            return false;
        }
        return true;
    }


    protected function writeStatus(string $file, string $s)
    {
        if (!($fp = fopen($file, 'wb')))
            return false;
        return fwrite($fp, $s);
    }
}







