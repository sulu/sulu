<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapped;

use Sulu\Component\PHPCR\Wrapper\Traits\PropertyTrait;
use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;
use PHPCR\PropertyInterface;
use Sulu\Component\PHPCR\Wrapper\WrapperAwareInterface;

class Property implements \IteratorAggregate, WrappedObjectInterface, PropertyInterface, WrapperAwareInterface
{
    use PropertyTrait;

    public function getIterator()
    {
        return $this->getWrappedObject()->getIterator();
    }
}
