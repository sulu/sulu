<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     */
    public function addLocation(GeolocatorLocation $location): void
    {
        $this->locations[] = $location;
    }

    /**
     * Return an array representation of the response.
     */
    public function toArray(): array
    {
        $res = [];
        foreach ($this->locations as $location) {
            $res[] = $location->toArray();
        }

        return $res;
    }

    /**
     * Return the number of locations in the response.
     */
    public function count(): int
    {
        return count($this->locations);
    }
}
