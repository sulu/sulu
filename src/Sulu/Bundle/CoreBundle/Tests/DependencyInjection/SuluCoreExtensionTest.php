<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\CoreBundle\DependencyInjection\SuluCoreExtension;

class SuluCoreExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.cache_dir', __DIR__);
        $this->container->setParameter('sulu.context', 'admin');
    }

    protected function getContainerExtensions()
    {
        return [
            new SuluCoreExtension(),
        ];
    }

    public function testLoadNoConfig()
    {
        $this->load([
            'content' => [
                'structure' => [
                    'default_type' => [
                        'snippet' => 'default',
                    ],
                    'paths' => [],
                    'type_map' => [
                        'page' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                        'home' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                        'snippet' => '\Sulu\Component\Content\Compat\Structure\SnippetBridge',
                    ],
                ],
            ],
            'locales' => ['en' => 'English', 'de' => 'Deutsch'],
            'translations' => ['de', 'en'],
            'fallback_locale' => 'en',
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'sulu.cache.warmer.structure', 'kernel.cache_warmer'
        );

        $this->assertEquals(
            'default',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );
    }

    public function testDefaults()
    {
        $this->load([
            'content' => [
                'structure' => [
                    'default_type' => [
                        'snippet' => 'barfoo',
                    ],
                    'paths' => [],
                    'type_map' => [
                        'page' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                        'home' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                        'snippet' => '\Sulu\Component\Content\Compat\Structure\SnippetBridge',
                    ],
                ],
            ],
            'locales' => ['en' => 'English', 'de' => 'Deutsch'],
            'translations' => ['de', 'en'],
            'fallback_locale' => 'en',
        ]);

        $this->assertEquals(
            'barfoo',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );
    }

    public function testLocales()
    {
        $this->load(
            [
                'content' => [
                    'structure' => [
                        'default_type' => [
                            'snippet' => 'barfoo',
                        ],
                        'paths' => [],
                        'type_map' => [
                            'page' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                            'home' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
                            'snippet' => '\Sulu\Component\Content\Compat\Structure\SnippetBridge',
                        ],
                    ],
                ],
                'locales' => ['en' => 'English', 'de' => 'Deutsch', 'fr' => 'France'],
                'translations' => ['de', 'en'],
                'fallback_locale' => 'en',
            ]
        );

        $this->assertEquals(
            ['en', 'de', 'fr'],
            $this->container->getParameter('sulu_core.locales')
        );
        $this->assertEquals(
            ['en' => 'English', 'de' => 'Deutsch', 'fr' => 'France'],
            $this->container->getParameter('sulu_core.translated_locales')
        );
        $this->assertEquals(
            ['de', 'en'],
            $this->container->getParameter('sulu_core.translations')
        );
        $this->assertEquals(
            'en',
            $this->container->getParameter('sulu_core.fallback_locale')
        );
    }
}
