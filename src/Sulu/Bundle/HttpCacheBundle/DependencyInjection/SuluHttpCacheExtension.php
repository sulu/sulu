<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $fosHttpCacheConfig = [
            'debug' => [
                'enabled' => $config['debug']['enabled'],
            ],
        ];

        if (!$container->hasExtension('sensio_framework_extra')) {
            $fosHttpCacheConfig['tags'] = [
                'annotations' => [
                    'enabled' => false,
                ],
            ];
        }

        if ($this->shouldCache($container)) {
            if ($config['proxy_client']['symfony']['enabled']) {
                $symfonyProxyClient = $config['proxy_client']['symfony'];
                $fosHttpCacheConfig['proxy_client']['symfony']['http']['servers'] =
                    count($symfonyProxyClient['servers']) ? $symfonyProxyClient['servers'] : ['127.0.0.1'];
            }

            if ($config['proxy_client']['varnish']['enabled']) {
                $varnishProxyClient = $config['proxy_client']['varnish'];

                $fosHttpCacheConfig['proxy_client']['varnish']['http']['servers'] =
                    count($varnishProxyClient['servers']) ? $varnishProxyClient['servers'] : ['127.0.0.1'];
            }

            if (array_key_exists('proxy_client', $fosHttpCacheConfig)) {
                $fosHttpCacheConfig['tags']['enabled'] = $config['tags']['enabled'];
            }
        }

        $container->prependExtensionConfig('fos_http_cache', $fosHttpCacheConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('event-subscribers.xml');
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_http_cache.cache.max_age', $config['cache']['max_age']);
        $container->setParameter('sulu_http_cache.cache.shared_max_age', $config['cache']['shared_max_age']);

        if (!$this->shouldCache($container)) {
            return;
        }

        $proxyClientAvailable = false;
        if (array_key_exists('proxy_client', $config)) {
            foreach ($config['proxy_client'] as $proxyClient) {
                if (true === $proxyClient['enabled']) {
                    $proxyClientAvailable = true;
                    break;
                }
            }
        }

        if ($proxyClientAvailable) {
            $loader->load('cache-manager.xml');
            $loader->load('cache-lifetime-enhancer.xml');

            if (true === $config['tags']['enabled']) {
                $loader->load('tags.xml');
            }
        }
    }

    /**
     * Returns boolean if system should cache in current environment.
     *
     * @param ContainerInterface $container
     *
     * @return bool
     */
    private function shouldCache(ContainerInterface $container): bool
    {
        return !in_array($container->getParameter('kernel.environment'), ['dev', 'test']);
    }
}
