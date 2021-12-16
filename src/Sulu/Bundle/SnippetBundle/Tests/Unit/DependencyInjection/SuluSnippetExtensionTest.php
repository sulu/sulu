<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\SnippetBundle\DependencyInjection\SuluSnippetExtension;

class SuluSnippetExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SuluSnippetExtension(),
        ];
    }

    public function testLoad(): void
    {
        $this->container->setParameter('kernel.bundles', []);

        $this->load([
            'twig' => [
                'snippet' => [
                    'cache_lifetime' => 20,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sulu_snippet.twig.snippet.cache_lifetime', 20);
        $this->assertContainerBuilderNotHasService('sulu_snippet.snippet_trash_subscriber');
    }

    public function testLoadWithTrashBundle(): void
    {
        $this->container->setParameter('kernel.bundles', ['SuluTrashBundle' => true]);

        $this->load([
            'twig' => [
                'snippet' => [
                    'cache_lifetime' => 20,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sulu_snippet.twig.snippet.cache_lifetime', 20);
        $this->assertContainerBuilderHasService('sulu_snippet.snippet_trash_subscriber');
    }
}
