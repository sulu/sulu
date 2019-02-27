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

use Sulu\Bundle\LocationBundle\Geolocator\Exception\GeolocatorNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container for geolocators.
 */
class GeolocatorManager
{
    /**
     * @var GeolocatorInterface[]
     */
    protected $geolocators = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register a geolocator with the given name.
     *
     * @param string $name
     * @param string $serviceId
     */
    public function register($name, $serviceId)
    {
        $this->geolocators[$name] = $serviceId;
    }

    /**
     * Retrieve the named name.
     *
     * @param string $name
     *
     * @return GeolocatorInterface
     */
    public function get($name)
    {
        if (!isset($this->geolocators[$name])) {
            throw new GeolocatorNotFoundException(
                sprintf(
                    'Attempt to retrieve unknown geolocator "%s"',
                    $name
                )
            );
        }

        return $this->container->get($this->geolocators[$name]);
    }
}
