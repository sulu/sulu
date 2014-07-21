<?php

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;

class NominatimGeolocator implements GeolocatorInterface
{
    protected $endpoint;

    public function __construct($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * {@inheritDoc}
     */
    public function locate($query)
    {
        $response = file_get_contents($foo);
    }
}
