<?php

namespace Sulu\Component\PHPCR\Wrapper;

use PHPCR\ObjectInterface;

/**
 * Object Wrapper Interface
 *
 * Object wrappers wrap custom classes around objects.
 */
interface WrapperInterface
{
    /**
     * Wrap a single node
     *
     * @param ObjectInterface $node
     */
    public function wrap($object, $className);

    /**
     * Wrap multiple nodes
     *
     * @param ObjectInterface[]
     */
    public function wrapMany($objects, $className);
}
