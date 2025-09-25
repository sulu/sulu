<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Symfony\CompilerPass;

use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

class SnippetAreaCompilerPassTest extends KernelTestCase
{
    use ProphecyTrait;

    public function testWithoutAreas(): void
    {
        // see packages/snippet/tests/Application/config/templates/snippets/snippet.xml for context
        $this->assertEquals(
            [
                'with-cache' => [
                    'title' => [
                        'en' => 'With cache',
                        'de' => 'Mit cache',
                    ],
                    'cache-invalidation' => true,
                    'areaKey' => 'with-cache',
                    'template' => 'snippet',
                ],
                'hotel' => [
                    'title' => [
                        'en' => 'Hotel Snippet',
                        'de' => 'Hotel Schnipsel',
                    ],
                    'cache-invalidation' => false,
                    'areaKey' => 'hotel',
                    'template' => 'snippet',
                ],
                'test' => [
                    'title' => [
                        'en' => 'Menu Social Media Links',
                    ],
                    'cache-invalidation' => false,
                    'areaKey' => 'test',
                    'template' => 'snippet',
                ],
            ],
            self::getContainer()->getParameter(SnippetAreaCompilerPass::SNIPPET_AREA_PARAM)
        );
    }

    public function testDuplicateAreaKeysThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Snippet area "hotel" must be unique. It is defined in both');

        $translator = $this->prophesize(TranslatorInterface::class);
        // Set up minimal translator expectations - the exception should happen before translation
        $translator->trans('snippet_type.hotel', [], 'admin', 'en')->willReturn('Hotel');
        $translator->trans('snippet_type.hotel', [], 'admin', 'de')->willReturn('Hotel');
        $translator->trans('', [], 'admin', 'en')->willReturn('');
        $translator->trans('', [], 'admin', 'de')->willReturn('');

        $container = new ContainerBuilder();
        $container->set('translator', $translator->reveal());
        $container->setParameter('sulu_core.locales', ['en', 'de']);
        $container->setParameter('sulu_admin.templates.configuration', [
            'snippet' => [
                'directories' => [
                    __DIR__ . '/../../../../Application/config/templates/snippets',
                    __DIR__ . '/../../../../Application/config/templates/snippet-duplicates',
                ],
            ],
        ]);

        $compilerPass = new SnippetAreaCompilerPass();
        $compilerPass->process($container);
    }

    public function testDuplicateAreaKeyErrorMessage(): void
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        // Set up minimal translator expectations - the exception should happen before translation
        $translator->trans('snippet_type.hotel', [], 'admin', 'en')->willReturn('Hotel');
        $translator->trans('snippet_type.hotel', [], 'admin', 'de')->willReturn('Hotel');
        $translator->trans('', [], 'admin', 'en')->willReturn('');
        $translator->trans('', [], 'admin', 'de')->willReturn('');

        $container = new ContainerBuilder();
        $container->set('translator', $translator->reveal());
        $container->setParameter('sulu_core.locales', ['en', 'de']);
        $container->setParameter('sulu_admin.templates.configuration', [
            'snippet' => [
                'directories' => [
                    __DIR__ . '/../../../../Application/config/templates/snippets',
                    __DIR__ . '/../../../../Application/config/templates/snippet-duplicates',
                ],
            ],
        ]);

        $compilerPass = new SnippetAreaCompilerPass();

        try {
            $compilerPass->process($container);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Snippet area "hotel" must be unique', $e->getMessage());
            $this->assertStringContainsString('snippet.xml', $e->getMessage());
            $this->assertStringContainsString('duplicate-hotel.xml', $e->getMessage());
        }
    }
}
