<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
        $loader->load('geolocator.xml');

        $this->configureContentTypes($config, $container);
        $this->configureMapManager($config, $container);

        $this->configureGeolocators($config, $container);
    }

    /**
     * Configure the sulu content types.
     *
     * @param array $config - Resolved configuration
     * @param ContainerBuilder
     */
    private function configureContentTypes($config, $container)
    {
        $container->setParameter(
            'sulu.content.type.location.template',
            $config['types']['location']['template']
        );
    }

    /**
     * Configure the map manager - register the providers and geolocators
     * with the map manager class.
     *
     * @param array $config - Resolved configuration
     * @param ContainerBuilder
     */
    private function configureMapManager($config, ContainerBuilder $container)
    {
        $mapManager = $container->getDefinition('sulu_location.map_manager');

        foreach ($config['enabled_providers'] as $enabledProviderName) {
            $providerConfig = $config['providers'][$enabledProviderName];
            $mapManager->addMethodCall('registerProvider', [
                $enabledProviderName,
                $providerConfig,
            ]);
        }

        foreach ($config['geolocators'] as $geoLocatorName => $geoLocatorOptions) {
            $mapManager->addMethodCall('registerGeolocator', [
                $geoLocatorName,
                $geoLocatorOptions,
            ]);
        }

        $mapManager->addMethodCall('setDefaultProviderName', [$config['default_provider']]);
    }

    /**
     * Configure the geolocator services.
     *
     * @param array $config - Resolved configuration
     * @param ContainerBuilder
     */
    private function configureGeolocators($config, $container)
    {
        $geolocatorName = $config['geolocator'];
        $container->setParameter('sulu_location.geolocator.name', $geolocatorName);

        $nominatim = function ($config, $container) {
            $endpoint = $config['geolocators']['nominatim']['endpoint'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.endpoint', $endpoint);
        };

        $google = function ($config, $container) {
            $apiKey = $config['geolocators']['google']['api_key'];
            $container->setParameter('sulu_location.geolocator.service.google.api_key', $apiKey);
        };

        $nominatim($config, $container);
        $google($config, $container);
    }
}
