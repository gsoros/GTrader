<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use GTrader\Skeleton;
use GTrader\HasCache;
use GTrader\Strategies\Fann as FannStrategy;

abstract class Training extends Model
{
    use Skeleton, HasCache;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '';

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

    protected $lock;


    abstract public function run();


    public function __construct(array $params = [])
    {
        $this->skeletonConstruct($params);
    }


    protected function obtainLock()
    {
        $this->lock = 'training_'.$this->id;
        if (!Lock::obtain($this->lock)) {
            throw new \Exception('Could not obtain training lock for '.$this->id);
        }
        return $this;
    }


    protected function releaseLock()
    {
        Lock::release($this->lock);
        return $this;
    }
}
