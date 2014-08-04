<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

trait WrappedObjectTrait
{
    protected $object;

    public function setWrappedObject($object)
    {
        $this->object = $object;
    }

    public function getWrappedObject()
    {
        return $this->object;
    }

    public function call($method, $args = array())
    {
        return call_user_func_array(array($this->getWrappedObject(), $method), $args);
    }
}
