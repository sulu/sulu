<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator;

use DateTime;

/**
 * holds information for one Resourcelocator and his history.
 */
class ResourceLocatorInformation
{
    /**
     * @var string
     */
    private $resourceLocator;

    /**
     * @var DateTime
     */
    private $created;

    /**
     * @var string
     */
    private $id;

    public function __construct($resourceLocator, $created, $id)
    {
        $this->created = $created;
        $this->resourceLocator = $resourceLocator;
        $this->id = $id;
    }

    /**
     * returns datetime of creation.
     *
     * @return \DateTime
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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
