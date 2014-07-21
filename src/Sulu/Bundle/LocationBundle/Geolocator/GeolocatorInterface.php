<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

interface GeolocatorInterface
{
    public function locate($query);
}
