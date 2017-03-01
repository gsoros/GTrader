<?php

namespace GTrader;

use GTrader\Lock;
use GTrader\FannTraining as Training;

class TrainingManager
{

    use Skeleton;


    public function run()
    {
        $lock = __METHOD__;
        if (!Lock::obtain($lock))
        {
            echo "Another TrainingManager process is running.\n";
            return false;
        }
        echo "TrainingManager:run()\n";

        while ($this->shouldRun())
        {
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
        echo "main()\n";
        // Check for any trainings
        $trainings = Training::where('status', 'training')->get();
        echo 'Trainings: '.count($trainings)."\n";
        foreach ($trainings as $training)
        {
            // Check if we have a free trainer slot
            while (is_null($slot = $this->getSlot()))
            {
                echo "No free slot\n";
                $this->sleep();
            }
            // Check if a trainer is already working on this training
            $training_lock = 'training_'.$training->id;
            if (Lock::obtain($training_lock))
            {
                // This training can be assigned to a worker
                Lock::release($training_lock);
                $this->assign($slot, $training);
            }
        }
    }


    protected function getSlot()
    {
        $slots = $this->getParam('slots');

        for ($i = 0; $i < $slots; $i++)
        {
            $slot_lock = 'slot_'.$i;
            if (Lock::obtain($slot_lock))
            {
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
        echo 'Assigning training '.$training->id.' to slot '.$slot."\n";

        $command = 'php '.base_path('artisan').' training:run '.$slot.' '.$training->id;

        echo $command."\n";

        if (substr(php_uname(), 0, 7) === "Windows")
            pclose(popen('start /B '. $command, 'r'));
        else
            exec($command.' >> /dev/null 2>&1 &');

        sleep(2); // allow child process some time to obtain lock
    }
}
