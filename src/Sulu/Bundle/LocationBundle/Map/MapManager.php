<?php

namespace Sulu\Bundle\LocationBundle\Map;

/**
 * Simple container for holding map provider informations.
 */
class MapManager
{
    protected $providers;
    protected $geoLocators;
    protected $defaultProviderName;

    public function registerProvider($name, $options)
    {
        $this->providers[$name] = $options;
    }

    public function registerGeolocator($name, $options)
    {
        $this->geolocators[$name] = $options;
    }

    public function getProvidersAsArray()
    {
        return $this->providers;
    }

    public function getDefaultProviderName()
    {
        return $this->defaultProviderName;
    }

    public function setDefaultProviderName($name)
    {
        $this->defaultProviderName = $name;
    }
}
