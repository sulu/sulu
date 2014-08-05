<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapper;

use Sulu\Component\PHPCR\Wrapper\WrapperInterface;
use Sulu\Component\PHPCR\Wrapper\WrapperAwareInterface;
use Sulu\Component\PHPCR\Wrapper\Exception\WrapperException;

/**
 * The simple mapper simply maps nodes to the given target
 * class.
 */
class SimpleWrapper implements WrapperInterface
{
    protected $classMap;

    public function __construct(array $classMap)
    {
        $this->classMap = $classMap;
    }

    public function wrap($object, $className)
    {
        if (!isset($this->classMap[$className])) {
            return $object;
        }

        $wrapperClass = $this->classMap[$className];

        $refl = new \ReflectionClass($wrapperClass);

        if (!$refl->isSubclassOf('Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface')) {
            throw new WrapperException(sprintf(
                'Wrapped class "%s" must implement WrappedObjectInterface',
                $wrapperClass
            ));
        }

        if (!$refl->isSubclassOf($className)) {
            throw new WrapperException(sprintf(
                'Wrapper class "%s" does not implement the interface for "%s"',
                $wrapperClass,
                $className
            ));
        }

        $wrappedNode = new $wrapperClass($node);
        $wrappedNode->setWrappedObject($object);

        if ($wrappedNode instanceof WrapperAwareInterface) {
            $wrappedNode->setWrapper($this);
        }

        return $wrappedNode;
    }

    public function wrapMany($collection, $className)
    {
        foreach ($collection as $key => &$node) {
            $collection[$key] = $this->wrap($node, $className);
        }

        return $collection;
    }
}
