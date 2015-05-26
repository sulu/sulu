<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

/**
 * Interface for geolocation services.
 */
interface GeolocatorInterface
{
    public function locate($query);
}
