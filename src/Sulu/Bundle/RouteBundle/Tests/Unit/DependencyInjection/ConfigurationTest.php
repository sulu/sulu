<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Sulu\Bundle\RouteBundle\DependencyInjection\Configuration;
use Sulu\Bundle\RouteBundle\DependencyInjection\SuluRouteExtension;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new SuluRouteExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testLoadMappingConfigWithDashUnderlines()
    {
        $config = [
            __DIR__ . '/../../app/Resources/test_config.yml',
        ];

        $this->assertProcessedConfigurationEquals(
            [
                'mappings' => [
                    'Sulu\Bundle\ArticleBundle\Document\ArticleDocument' => [
                        'generator' => 'template',
                        'options' => [
                            'test' => '/{object.getTitle()}',
                            'test-dash' => '/{object.getDashTitle()}',
                            'test_underline' => '/{object.getUnderlineTitle()}',
                        ],
                    ],
                ],
                'content_types' => [
                    'route' => [
                        'template' => '@Test/route.html.twig',
                    ],
                ],
                'objects' => [
                    'route' => [
                        'model' => 'TestRouteEntity',
                        'repository' => 'TestRouteRepository',
                    ],
                ],
            ],
            $config
        );
    }
}
