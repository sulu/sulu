<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

use Traversable;

/**
 * A collection of all portals in a specific sulu installation
 * @package Sulu\Component\Portal
 */
class PortalCollection implements \IteratorAggregate
{
    /**
     * All the portals in a specific sulu installation
     * @var Portal[]
     */
    private $portals;

    /**
     * Contains all the resources, which where used to build this collection.
     * Is required by the Symfony CacheConfig-Component.
     * @var array
     */
    private $resources;

    /**
     * Adds the portal with its unique key as array key to the collection
     * @param Portal $portal The portal to add
     */
    public function add(Portal $portal)
    {
        $this->portals[$portal->getKey()] = $portal;
    }

    /**
     * Returns the resources used to build this collection
     * @return array The resources build to use this collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->portals);
    }

    /**
     * Returns the content of these portals as array
     * @return array
     */
    public function toArray()
    {
        $portals = array();

        foreach($this->portals as $portal) {
            $portalData = array();
            $portalData['name'] = $portal->getName();
            $portalData['key'] = $portal->getKey();
            $portals[] = $portalData;
        }

        return $portals;
    }
}
