<?php

namespace GTrader;

trait HasOwner
{
    protected $allowed_owners = [];
    protected $owner;


    public function getOwner()
    {
        return $this->owner;
    }


    public function setOwner(&$owner)
    {
        if ($this->canBeOwnedBy($owner)) {
            $this->owner = $owner;
            return $this;
        }
        throw new \Exception('Class '.get_class($owner).' is not allowed as owner.');
    }


    public function canBeOwnedBy(&$owner)
    {
        $owner_class = get_class($owner);
        foreach ($this->getAllowedOwners() as $allowed) {
            if ($allowed === $owner_class || is_subclass_of($owner, $allowed)) {
                return true;
            }
        }
        return false;
    }


    public function getAllowedOwners()
    {
        return $this->allowed_owners;
    }
}
