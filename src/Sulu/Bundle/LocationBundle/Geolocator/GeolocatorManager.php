<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container for geolocators.
 */
class GeolocatorManager
{
    protected $geolocatorMap = array();
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register a geolocator with the given name.
     */
    public function register($name, $serviceId)
    {
        $this->geolocators[$name] = $serviceId;
    }

    /**
     * Retrieve the named name.
     */
    public function get($name)
    {
        if (!isset($this->geolocators[$name])) {
            throw new Exception\GeolocatorNotFoundException(sprintf(
                'Attempt to retrieve unknown geolocator "%s"', $name
            ));
        }

        return $this->container->get($this->geolocators[$name]);
    }
}
