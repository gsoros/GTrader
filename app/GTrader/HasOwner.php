<?php

namespace GTrader;

trait HasOwner
{
    protected $owner;

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
}
