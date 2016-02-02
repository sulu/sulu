<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
