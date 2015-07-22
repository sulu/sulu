<?php

/*
 * This file is part of the Sulu.
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
    protected function getContainerExtensions()
    {
        return [
            new SuluCoreExtension(),
        ];
    }

    public function testLoadNoConfig()
    {
        $this->load([
            'locales' => ['en', 'de'],
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
                ],
            ],
            'locales' => ['en', 'de'],
        ]);

        $this->assertEquals(
            'barfoo',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );
    }
}
