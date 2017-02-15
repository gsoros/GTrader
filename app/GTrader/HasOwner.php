<?php

namespace GTrader;

trait HasOwner
{
    protected $_allowed_owners = [];
    protected $_owner;


    public function getOwner()
    {
        return $this->_owner;
    }


    public function setOwner(&$owner)
    {
        if ($this->canBeOwnedBy($owner))
        {
            $this->_owner = $owner;
            return $this;
        }
        throw new \Exception('Class '.get_class($owner).' is not allowed as owner.');
    }


    public function canBeOwnedBy(&$owner)
    {
        $owner_class = get_class($owner);
        foreach ($this->_allowed_owners as $allowed)
            if ($allowed === $owner_class || is_subclass_of($owner, $allowed))
                return true;
        return false;
    }
}
