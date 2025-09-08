<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\DependencyInjection;

use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ExampleTestExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'resources' => [
                        Example::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'example_test.get_examples',
                                'detail' => 'example_test.get_example',
                            ],
                        ],
                    ],
                    'templates' => [
                        Example::TEMPLATE_TYPE => [
                            'default_type' => 'default', // TODO should not be hardcoded?
                            'directories' => [
                                'app' => '%kernel.project_dir%/config/templates/examples',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_route')) {
            $container->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        Example::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => Example::RESOURCE_KEY,
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

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
