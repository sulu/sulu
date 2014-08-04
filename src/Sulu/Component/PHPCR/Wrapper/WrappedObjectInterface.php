<?php

namespace Sulu\Component\PHPCR\Wrapper;

interface WrappedObjectInterface
{
    /**
     * Set the wrapped object
     *
     * @param object
     */
    public function setWrappedObject($object);

    /**
     * Return the wrapped PHPCR node
     *
     * @return object
     */
    public function getWrappedObject();
}
