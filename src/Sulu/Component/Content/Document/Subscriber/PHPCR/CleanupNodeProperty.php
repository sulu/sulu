<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber\PHPCR;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface;
use PHPCR\PropertyInterface;

/**
 * Wraps a PHPCR Property to prevent remove() from leaking through the
 * shared ObjectManager when used on a cloned node in the cleanup command.
 *
 * @internal
 *
 * @implements \IteratorAggregate<mixed, mixed>
 */
class CleanupNodeProperty implements \IteratorAggregate, PropertyInterface
{
    public function __construct(
        private PropertyInterface $property,
    ) {
    }

    public function remove(): void
    {
        // No-op: prevent remove() from reaching the ObjectManager
        // and corrupting the original node's state.
    }

    public function setValue($value, $type = null)
    {
        $this->property->setValue($value, $type);
    }

    public function addValue($value)
    {
        $this->property->addValue($value);
    }

    public function getValue()
    {
        return $this->property->getValue();
    }

    public function getString()
    {
        return $this->property->getString();
    }

    public function getBinary()
    {
        return $this->property->getBinary();
    }

    public function getLong()
    {
        return $this->property->getLong();
    }

    public function getDouble()
    {
        return $this->property->getDouble();
    }

    public function getDecimal()
    {
        return $this->property->getDecimal();
    }

    public function getDate()
    {
        return $this->property->getDate();
    }

    public function getBoolean()
    {
        return $this->property->getBoolean();
    }

    public function getNode()
    {
        return $this->property->getNode();
    }

    public function getProperty()
    {
        return $this->property->getProperty();
    }

    public function getLength()
    {
        return $this->property->getLength();
    }

    public function getDefinition()
    {
        return $this->property->getDefinition();
    }

    public function getType()
    {
        return $this->property->getType();
    }

    public function isMultiple()
    {
        return $this->property->isMultiple();
    }

    public function getPath()
    {
        return $this->property->getPath();
    }

    public function getName()
    {
        return $this->property->getName();
    }

    public function getAncestor($depth)
    {
        return $this->property->getAncestor($depth);
    }

    public function getParent()
    {
        return $this->property->getParent();
    }

    public function getDepth()
    {
        return $this->property->getDepth();
    }

    public function getSession()
    {
        return $this->property->getSession();
    }

    public function isNode()
    {
        return $this->property->isNode();
    }

    public function isNew()
    {
        return $this->property->isNew();
    }

    public function isModified()
    {
        return $this->property->isModified();
    }

    public function isSame(ItemInterface $otherItem)
    {
        return $this->property->isSame($otherItem);
    }

    public function accept(ItemVisitorInterface $visitor)
    {
        $this->property->accept($visitor);
    }

    public function revert()
    {
        $this->property->revert();
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        if ($this->property instanceof \IteratorAggregate) {
            return $this->property->getIterator();
        }

        $value = $this->getValue();
        if (!\is_array($value)) {
            $value = [$value];
        }

        return new \ArrayIterator($value);
    }
}
