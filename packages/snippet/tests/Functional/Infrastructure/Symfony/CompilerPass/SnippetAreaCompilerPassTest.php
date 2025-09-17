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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

class SnippetAreaCompilerPassTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContainerBuilder>
     */
    protected $container;

    private SnippetAreaCompilerPass $compiler;

    public function setUp(): void
    {
        $this->compiler = new SnippetAreaCompilerPass(__DIR__);

        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->container->getParameter('sulu_core.locales')->willReturn(['en', 'de']);
    }

    public function testWithoutAreas(): void
    {
        $structureMetaData1 = $this->createStructureMetaData('test');
        $structureMetaData2 = $this->createStructureMetaData('hotel');

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'test' => [
                    'key' => 'test',
                    'template' => 'test',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'de' => 'Test DE',
                        'en' => 'Test EN',
                    ],
                ],
                'hotel' => [
                    'key' => 'hotel',
                    'template' => 'hotel',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'de' => 'Hotel DE',
                        'en' => 'Hotel EN',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $this->compiler->process($this->container->reveal());
    }

    public function testWithOnlyOneTranslation(): void
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans()->shouldNotBeCalled();

        $this->container->get('translator')->shouldNotBeCalled();

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'article' => [
                    'key' => 'article',
                    'template' => 'test',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'en' => 'Article EN',
                        'de' => 'Test DE Article',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $this->compiler->process($this->container->reveal());
    }

    public function testWithAreas(): void
    {
        $structureMetaData1 = $this->createStructureMetaData(
            'test',
            [
                'article' => [
                    'key' => 'article',
                    'cache-invalidation' => 'true',
                    'title' => [
                        'de' => 'Artikel Test',
                        'en' => 'Article Test',
                    ],
                ],
            ]
        );
        $structureMetaData2 = $this->createStructureMetaData('hotel');

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'article' => [
                    'key' => 'article',
                    'template' => 'test',
                    'cache-invalidation' => 'true',
                    'title' => [
                        'de' => 'Artikel Test',
                        'en' => 'Article Test',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $this->compiler->process($this->container->reveal());
    }

    public function testWithTranslatedAreas(): void
    {
        $structureMetaData = $this->createStructureMetaData(
            'test',
            [
                'article' => [
                    'key' => 'article',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'sulu_snippet.snippet_area',
                    ],
                ],
            ]
        );

        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->structureFactory->getStructures('snippet')->willReturn([$structureMetaData->reveal()]);

        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans('sulu_snippet.snippet_area', [], 'admin', 'en')->willReturn('Article Test');
        $translator->trans('sulu_snippet.snippet_area', [], 'admin', 'de')->willReturn('Artikel Test');
        $this->container->get('translator')->willReturn($translator->reveal());

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'article' => [
                    'key' => 'article',
                    'template' => 'test',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'en' => 'Article Test',
                        'de' => 'Artikel Test',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $this->compiler->process($this->container->reveal());
    }

    public function testWithTranslatedAreaButNoTranslation(): void
    {
        $structureMetaData = $this->createStructureMetaData(
            'test',
            [
                'article' => [
                    'key' => 'article',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'sulu_snippet.areas.article.title',
                    ],
                ],
            ]
        );

        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->structureFactory->getStructures('snippet')->willReturn([$structureMetaData->reveal()]);

        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::any(), [], 'admin', Argument::any())->willReturnArgument(0);

        $this->container->get('translator')->willReturn($translator->reveal());

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'article' => [
                    'key' => 'article',
                    'template' => 'test',
                    'cache-invalidation' => 'false',
                    'title' => [
                        'en' => 'sulu_snippet.areas.article.title',
                        'de' => 'sulu_snippet.areas.article.title',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $this->compiler->process($this->container->reveal());
    }
}
