<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\CoreBundle\DependencyInjection\SuluCoreExtension;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;

class SuluCoreExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.project_dir', \dirname(__DIR__));
        $this->container->setParameter('kernel.cache_dir', \dirname(__DIR__) . '/var/cache');
        $this->container->setParameter('sulu.context', 'admin');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new SuluCoreExtension(),
        ];
    }

    public function testLoadNoConfig(): void
    {
        $this->load([
            'content' => [
                'structure' => [
                    'default_type' => [
                        'snippet' => 'default',
                        'test' => 'default_test',
                    ],
                    'paths' => [],
                    'type_map' => [
                        'page' => '\\' . PageBridge::class,
                        'home' => '\\' . PageBridge::class,
                        'snippet' => '\\' . SnippetBridge::class,
                    ],
                ],
            ],
            'locales' => ['en' => 'English', 'de' => 'Deutsch'],
            'translations' => ['de', 'en'],
            'fallback_locale' => 'en',
        ]);

        $this->assertEquals(
            'default',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );

        $this->assertEquals(
            'default_test',
            $this->container->getParameter('sulu.content.structure.default_type.test')
        );
    }

    public function testDefaults(): void
    {
        $this->load([
            'content' => [
                'structure' => [
                    'default_type' => [
                        'snippet' => 'barfoo',
                    ],
                    'paths' => [],
                    'type_map' => [
                        'page' => '\\' . PageBridge::class,
                        'home' => '\\' . PageBridge::class,
                        'snippet' => '\\' . SnippetBridge::class,
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

    public function testLocales(): void
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
                            'page' => '\\' . PageBridge::class,
                            'home' => '\\' . PageBridge::class,
                            'snippet' => '\\' . SnippetBridge::class,
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
