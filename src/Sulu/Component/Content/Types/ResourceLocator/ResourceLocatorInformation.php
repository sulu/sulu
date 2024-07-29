<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator;

/**
 * holds information for one Resourcelocator and his history.
 */
class ResourceLocatorInformation
{
    /**
     * @param string $resourceLocator
     * @param \DateTime|null $created
     * @param string|int $id
     */
    public function __construct(private $resourceLocator, private $created, private $id)
    {
    }

    /**
     * returns datetime of creation.
     *
     * @return \DateTime|null
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * returns resource locator string.
     *
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }
}
