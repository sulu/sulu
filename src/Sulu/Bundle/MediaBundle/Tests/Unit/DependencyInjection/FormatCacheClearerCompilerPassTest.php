<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\FormatCacheClearerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Test the image transformation compiler pass.
 */
class FormatCacheClearerCompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormatCacheClearerCompilerPass());
    }

    public function testFormatCacheTag()
    {
        $formatCacheClearer = new Definition();
        $this->setDefinition('sulu_media.format_cache_clearer', $formatCacheClearer);

        $formatCache = new Definition();
        $formatCache->addTag('sulu_media.format_cache', [
            'alias' => 'local',
        ]);
        $this->setDefinition('sulu_media.format_cache', $formatCache);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.format_cache_clearer',
            'add',
            [
                new Reference('sulu_media.format_cache'),
                'local',
            ]
        );
    }
}
