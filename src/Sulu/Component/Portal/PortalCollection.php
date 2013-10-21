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
     * Adds the portal with its unique key as array key to the collection
     * @param Portal $portal The portal to add
     */
    public function add(Portal $portal)
    {
        $this->portals[$portal->getKey()] = $portal;
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
}
