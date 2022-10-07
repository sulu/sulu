<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Sulu\Bundle\RouteBundle\DependencyInjection\Configuration;
use Sulu\Bundle\RouteBundle\DependencyInjection\SuluRouteExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension(): ExtensionInterface
    {
        return new SuluRouteExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    public function testLoadMappingConfigWithDashUnderlines(): void
    {
        $config = [
            __DIR__ . '/../../Application/config/test_config.yml',
        ];

        $this->assertProcessedConfigurationEquals(
            [
                'mappings' => [
                    'Sulu\Bundle\ArticleBundle\Document\ArticleDocument' => [
                        'resource_key' => 'articles',
                        'generator' => 'template',
                        'options' => [
                            'test' => '/{object.getTitle()}',
                            'test-dash' => '/{object.getDashTitle()}',
                            'test_underline' => '/{object.getUnderlineTitle()}',
                        ],
                    ],
                ],
                'objects' => [
                    'route' => [
                        'model' => 'TestRouteEntity',
                        'repository' => 'TestRouteRepository',
                    ],
                ],
                'content_types' => [
                    'page_tree_route' => [
                        'page_route_cascade' => 'request',
                    ],
                ],
            ],
            $config
        );
    }
}
