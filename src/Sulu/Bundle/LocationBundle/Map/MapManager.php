<?php

namespace Sulu\Bundle\LocationBundle\Map;

class MapManager
{
    protected $providers;
    protected $defaultProviderName;

    public function registerProvider($name, $options)
    {
        $this->providers[$name] = $options;
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
