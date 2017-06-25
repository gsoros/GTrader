<?php

namespace GTrader;

use GTrader\FannTraining as Training;

class TrainingManager
{

    use Skeleton;


    public function run()
    {
        $lock = str_replace('::', '_', str_replace('\\', '_', __METHOD__));
        if (!Lock::obtain($lock)) {
            return false;
        }
        echo "TrainingManager:run()\n";

        while ($this->shouldRun()) {
            $this->main();
            $this->sleep();
        }
        Lock::release($lock);
        return true;
    }


    public static function getSlotCount()
    {
        return self::singleton()->getParam('slots');
    }


    protected function shouldRun()
    {
        // TODO check for the presence of a run file e.g. storage/run/TrainingManager
        return true;
    }

    protected function main()
    {
        // Check for any trainings
        $trainings = Training::where('status', 'training')->get();
        foreach ($trainings as $training) {
            // Check if we have a free trainer slot
            while (is_null($slot = $this->getSlot())) {
                echo "No free slot\n";
                $this->sleep();
            }
            // Check if a trainer is already working on this training
            $training_lock = 'training_'.$training->id;
            if (Lock::obtain($training_lock)) {
                // This training can be assigned to a worker
                Lock::release($training_lock);
                $this->assign($slot, $training);
            }
        }
    }


    protected function getSlot()
    {
        $slots = $this->getParam('slots');

        for ($i = 0; $i < $slots; $i++) {
            $slot_lock = 'training_slot_'.$i;
            if (Lock::obtain($slot_lock)) {
                Lock::release($slot_lock);
                return $i;
            }
        }
        return null;
    }


    protected function sleep()
    {
        sleep($this->getParam('wait_for_slot'));
    }


    protected function assign(int $slot, Training $training)
    {
        error_log('Assigning training '.$training->id.' to slot '.$slot);

        $command = $this->getParam('php_command').' '.
                    base_path('artisan').' training:run '.
                    $slot.' '.$training->id;


        if (substr(php_uname(), 0, 7) === "Windows") {
            pclose(popen('start /B '. $command, 'r'));
        } else {
            $strategy = Strategy::load($training->strategy_id);
            $prefix = $strategy->getParam('training_log_prefix', 'fanntraining_');
            $log_file = $prefix ? storage_path('logs/'.$prefix.$training->strategy_id.'.log') : '/dev/null';
            $command = $command.' >> '.$log_file.' 2>&1 &';
            error_log('command: '.$command);
            exec($command);
        }

        sleep(2); // allow child process some time to obtain lock
    }
}
