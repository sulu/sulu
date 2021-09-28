<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extends the container and initializes the preview budle.
 */
class SuluPreviewExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_preview.mode', $config['mode']);
        $container->setParameter('sulu_preview.delay', $config['delay']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                PreviewLinkRepositoryInterface::class => 'sulu.repository.preview_link',
            ]
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        if ($config['cache']['type']) {
            if (!$container->hasExtension('doctrine_cache')) {
                throw new \RuntimeException(
                    'Deprecated "sulu_preview.cache" configuration used, but DoctrineCacheBundle was not registered.' . \PHP_EOL .
                    'Register the DoctrineCacheBundle or use the "sulu_preview.cache_adapter" configuration.'
                );
            }

            $container->prependExtensionConfig('doctrine_cache',
                [
                    'aliases' => [
                        'sulu_preview.preview.cache' => 'sulu_preview',
                    ],
                    'providers' => [
                        'sulu_preview' => $config['cache'],
                    ],
                ]
            );
        } else {
            $container->prependExtensionConfig('framework',
                [
                    'cache' => [
                        'pools' => [
                            'sulu_preview.preview.cache' => [
                                'adapter' => $config['cache_adapter'],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluPreviewBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../../../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\PreviewBundle\Domain\Model',
                                'alias' => 'SuluPreviewBundle',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        PreviewLinkInterface::RESOURCE_KEY => [
                            'routes' => [
                                'detail' => 'sulu_preview.get_preview-link',
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
