<?php

namespace Sulu\Bundle\LocationBundle\Geolocator;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container for geolocators
 *
 * @author Daniel Leech <daniel@massiveart.com>
 */
class GeolocatorManager
{
    protected $geolocatorMap = array();
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register($alias, $serviceId)
    {
        $this->geolocators[$alias] = $serviceId;
    }

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
