<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Container extension for sulu-http-cache bundle.
 */
class SuluHttpCacheExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (in_array($container->getParameter('kernel.environment'), ['dev', 'test'])) {
            $container->prependExtensionConfig(
                'sulu_http_cache',
                [
                    'handlers' => [
                        'public' => ['enabled' => false],
                        'url' => ['enabled' => false],
                        'tags' => ['enabled' => false],
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('event-subscribers.xml');
        $loader->load('proxy-client.xml');
        $loader->load('structure-cache-handlers.xml');
        $loader->load('services.xml');

        $this->configureProxyClient($config['proxy_client'], $container);
        $this->configureStructureCacheHandlers($config['handlers'], $container);

        $container->setAlias('sulu_http_cache.handler', 'sulu_http_cache.handler.aggregate');
    }

    /**
     * Configure the proxy client services.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function configureProxyClient($config, ContainerBuilder $container)
    {
        $this->configureProxyClientVarnish($config['varnish'], $container);

        // get default
        $proxyClientName = null;
        foreach ($config as $name => $proxyClient) {
            if ($proxyClient['enabled'] === false) {
                continue;
            }

            if (null !== $proxyClientName) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Cannot enable more than one proxy, trying to enable "%s" when "%s" is already enabled',
                        $name,
                        $proxyClientName
                    )
                );
            }

            $proxyClientName = $name;
        }

        if (null === $proxyClientName) {
            $proxyClientName = 'symfony';
        }

        $container->setParameter('sulu_http_cache.proxy_client.name', $proxyClientName);
        $container->setAlias('sulu_http_cache.proxy_client', 'sulu_http_cache.proxy_client.' . $proxyClientName);
    }

    /**
     * Configure the varnish services.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function configureProxyClientVarnish($config, ContainerBuilder $container)
    {
        foreach ($config as $key => $value) {
            $container->setParameter(
                $this->getAlias() . '.proxy_client.varnish.' . $key,
                $value
            );
        }
    }

    /**
     * Configure the structure cache handler services.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function configureStructureCacheHandlers($config, ContainerBuilder $container)
    {
        $enabledHandlers = [];

        // remove handlers which have not been enabled
        foreach ($config as $handlerName => $handlerConfig) {
            if (false === $handlerConfig['enabled']) {
                $container->removeDefinition('sulu_http_cache.handler.' . $handlerName);
                continue;
            }

            $enabledHandlers[] = $handlerName;
        }

        $container->setParameter('sulu_http_cache.handler.public.max_age', $config['public']['max_age']);
        $container->setParameter('sulu_http_cache.handler.public.shared_max_age', $config['public']['shared_max_age']);
        $container->setParameter('sulu_http_cache.handler.public.use_page_ttl', $config['public']['use_page_ttl']);

        $container->setParameter('sulu_http_cache.handler.aggregate.handlers', $enabledHandlers);
    }
}
