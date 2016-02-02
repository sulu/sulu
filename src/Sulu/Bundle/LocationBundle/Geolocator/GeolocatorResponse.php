<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator;

/**
 * Container for aggregating geolocations.
 */
class GeolocatorResponse implements \Countable
{
    protected $locations = [];

    /**
     * Add a location to the response.
     *
     * @param GeolocatorLocation $location
     */
    public function addLocation(GeolocatorLocation $location)
    {
        $this->locations[] = $location;
    }

    /**
     * Return an array representation of the response.
     *
     * @return array
     */
    public function toArray()
    {
        $res = [];
        foreach ($this->locations as $location) {
            $res[] = $location->toArray();
        }

        return $res;
    }

    /**
     * Return the number of locations in the response.
     *
     * @return int
     */
    public function count()
    {
        return count($this->locations);
    }
}
