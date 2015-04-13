<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Types\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Compat\Structure\Structure;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Document\Property\PropertyValue;

/**
 * Wrapper for property conatiner
 */
class PropertyContainerWrapper implements PropertyContainerInterface
{
    private $container;

    public function __construct(PropertyContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Return the named property and evaluate its content
     *
     * @param string $name
     */
    public function getProperty($name)
    {
        return $this->container->getProperty($name);
    }

    public function hasProperty($name)
    {
        return $this->container->offsetExists($name);
    }

    public function offsetExists($offset)
    {
        return $this->container->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->container->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->container->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->container->offsetUnset($offset);
    }

    public function toArray()
    {
        return $this->container->toArray();
    }

    public function bind($data, $clearMissing)
    {
        return $this->container->bind($data, $clearMissing);
    }
}

