<?php

namespace GTrader;

trait HasOwner
{
    use Visualizable {
        visualize as __Visualizable__visualize;
    }

    protected $owner;


    public function kill()
    {
        //Log::debug('.');
        $this->unsetOwner();
    }


    public function getOwner()
    {
        return $this->owner;
    }


    public function setOwner($owner)
    {
        if ($this->canBeOwnedBy($owner)) {
            $this->owner = $owner;
            return $this;
        }
        throw new \Exception('Class '.get_class($owner).' is not allowed as owner.');
    }


    public function unsetOwner()
    {
        $this->owner = null;
    }


    public function canBeOwnedBy($owner)
    {
        foreach ($this->getAllowedOwners() as $allowed) {
            if ($owner->isClass($allowed)) {
                return true;
            }
        }
        return false;
    }


    public function getAllowedOwners()
    {
        return $this->getParam('allowed_owners', []);
    }


    public function setAllowedOwners(array $owners)
    {
        return $this->setParam('allowed_owners', $owners);
    }


    public function addAllowedOwner($owner)
    {
        if (is_object($owner)) {
            $owner = get_class($owner);
        }
        if (in_array($owner, $owners = $this->getAllowedOwners())) {
            return $this;
        }
        return $this->setAllowedOwners(array_merge($owners, [$owner]));
    }


    public function visualize(int $depth = 100)
    {
        $this->__Visualizable__visualize($depth);
        if (!$depth--) {
            return $this;
        }
        if ($node = $this->getOwner()) {
            /*
            if (!$this->visNodeExists($node)) {
                if (method_exists($node, 'visualize')) {
                    $node->visualize($depth);
                }
            }
            */
            $this->visAddEdge($this, $node,[
                'title' => $node->getShortClass().' owns '.$this->getShortClass(),
                'color' => '#620808',
                'arrows' => '',
            ]);
        }
        return $this;
    }
}
