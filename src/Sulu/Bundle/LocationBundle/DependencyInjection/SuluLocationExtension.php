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
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SuluLocationExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'geolocator_locations' => [
                            'routes' => [
                                'list' => 'sulu_location.geolocator_query',
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configureGeolocators($config, $container, $loader);
    }

    /**
     * @return void
     */
    private function configureGeolocators(array $config, ContainerBuilder $container, FileLoader $loader)
    {
        $geolocatorName = $config['geolocator'] ?? null;
        $geolocators = $config['geolocators'] ?? null;

        $loader->load('geolocator.php');

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

        $mapquest = function(array $geolocators, ContainerBuilder $container) {
            $apiKey = $geolocators['mapquest']['api_key'];
            $endpoint = $geolocators['mapquest']['endpoint'];
            $container->setParameter('sulu_location.geolocator.service.mapquest.api_key', $apiKey);
            $container->setParameter('sulu_location.geolocator.service.mapquest.endpoint', $endpoint);
        };

        $nominatim($geolocators, $container);
        $google($geolocators, $container);
        $mapquest($geolocators, $container);
    }
}
