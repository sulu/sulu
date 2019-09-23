<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluLocationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configureGeolocators($config, $container, $loader);
    }

    private function configureGeolocators(array $config, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $geolocatorName = $config['geolocator'] ?? null;
        $geolocators = $config['geolocators'] ?? null;

        $loader->load('geolocator.xml');

        $container->setParameter('sulu_location.geolocator.name', $geolocatorName);
        $container->setAlias('sulu_location.geolocator', 'sulu_location.geolocator.service.' . $geolocatorName);

        $nominatim = function(array $geolocators, ContainerBuilder $container) {
            $apiKey = $geolocators['nominatim']['api_key'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.api_key', $apiKey);

            $endpoint = $geolocators['nominatim']['endpoint'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.endpoint', $endpoint);
        };

        $google = function(array $geolocators, ContainerBuilder $container) {
            $apiKey = $geolocators['google']['api_key'];
            $container->setParameter('sulu_location.geolocator.service.google.api_key', $apiKey);
        };

        $nominatim($geolocators, $container);
        $google($geolocators, $container);
    }
}
