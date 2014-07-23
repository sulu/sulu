<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

/**
 * Container for aggregating geolocations
 */
class GeolocatorResponse implements \Countable
{
    protected $locations = array();

    public function addLocation(GeolocatorLocation $location)
    {
        $this->locations[] = $location;
    }

    public function toArray()
    {
        $res = array();
        foreach ($this->locations as $location) {
            $res[] = $location->toArray();
        }

        return $res;
    }

    public function count()
    {
        return count($this->locations);
    }
}
