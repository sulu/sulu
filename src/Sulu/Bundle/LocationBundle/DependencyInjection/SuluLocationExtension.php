<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SuluLocationExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('geolocator.xml');

        $this->configureContentTypes($config, $container);
        $this->configureMapManager($config, $container);

        $this->configureGeolocators($config, $container);
    }

    private function configureContentTypes($config, $container)
    {
        $container->setParameter(
            'sulu.content.type.location.template',
            $config['types']['location']['template']
        );
    }

    private function configureMapManager($config, $container)
    {
        $mapManager = $container->getDefinition('sulu_location.map_manager');

        foreach ($config['enabled_providers'] as $enabledProviderName) {
            $providerConfig = $config['providers'][$enabledProviderName];
            $mapManager->addMethodCall('registerProvider', array(
                $enabledProviderName,
                $providerConfig
            ));
        }

        foreach ($config['geolocators'] as $geoLocatorName => $geoLocatorOptions) {
            $mapManager->addMethodCall('registerGeolocator', array(
                $geoLocatorName,
                $geoLocatorOptions,
            ));
        }

        $mapManager->addMethodCall('setDefaultProviderName', array($config['default_provider']));
    }

    private function configureGeolocators($config, $container)
    {
        $nominatim = function ($config, $container) {
            $endpoint = $config['geolocators']['nominatim']['endpoint'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.endpoint', $endpoint);
        };

        $nominatim($config, $container);
    }
}
