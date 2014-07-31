<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapped;

use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;
use Sulu\Component\PHPCR\Wrapper\Traits\NodeTrait;
use PHPCR\NodeInterface;
use Sulu\Component\PHPCR\Wrapper\WrapperAwareInterface;

class Node implements \IteratorAggregate, WrappedObjectInterface, NodeInterface, WrapperAwareInterface
{
    use NodeTrait;

    public function getIterator()
    {
        return $this->getNodes();
    }
}
