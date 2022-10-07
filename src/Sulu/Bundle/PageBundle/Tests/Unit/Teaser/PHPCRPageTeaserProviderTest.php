<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Teaser;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\PHPCRPageTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PHPCRPageTeaserProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContentQueryExecutorInterface>
     */
    private $contentQueryExecutor;

    /**
     * @var ObjectProphecy<ContentQueryBuilderInterface>
     */
    private $contentQueryBuilder;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var PHPCRPageTeaserProvider
     */
    private $phpcrPageTeaserProvider;

    protected function setUp(): void
    {
        $this->contentQueryExecutor = $this->prophesize(ContentQueryExecutorInterface::class);
        $this->contentQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->phpcrPageTeaserProvider = new PHPCRPageTeaserProvider(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->structureMetadataFactory->reveal(),
            $this->translator->reveal(),
            false,
            [
                'view' => 64,
                'add' => 32,
                'edit' => 16,
                'delete' => 8,
                'archive' => 4,
                'live' => 2,
                'security' => 1,
            ]
        );
    }

    public function testConfiguration(): void
    {
        $configuration = $this->phpcrPageTeaserProvider->getConfiguration();

        $viewProperty = new \ReflectionProperty(TeaserConfiguration::class, 'view');
        $viewProperty->setAccessible(true);

        $resultToViewProperty = new \ReflectionProperty(TeaserConfiguration::class, 'resultToView');
        $resultToViewProperty->setAccessible(true);

        $this->assertEquals('sulu_page.page_edit_form', $viewProperty->getValue($configuration));
        $this->assertEquals(
            ['id' => 'id', 'attributes/webspaceKey' => 'webspace'],
            $resultToViewProperty->getValue($configuration)
        );
    }

    public function testFindNew(): void
    {
        $defaultTeaserDescriptionProperty = $this->prophesize(PropertyMetadata::class);
        $defaultTeaserDescriptionProperty->getName()->willReturn('description');
        $defaultTeaserMediaProperty = $this->prophesize(PropertyMetadata::class);
        $defaultTeaserMediaProperty->getName()->willReturn('image');
        $defaultMetadata = $this->prophesize(StructureMetadata::class);
        $defaultMetadata->getName()->willReturn('default');
        $defaultMetadata->hasPropertyWithTagName(Argument::cetera())->willReturn(true);
        $defaultMetadata->getPropertyByTagName('sulu.teaser.description')->willReturn($defaultTeaserDescriptionProperty->reveal());
        $defaultMetadata->getPropertyByTagName('sulu.teaser.media')->willReturn($defaultTeaserMediaProperty->reveal());

        $otherTeaserDescriptionProperty = $this->prophesize(PropertyMetadata::class);
        $otherTeaserDescriptionProperty->getName()->willReturn('article');
        $otherTeaserMediaProperty = $this->prophesize(PropertyMetadata::class);
        $otherTeaserMediaProperty->getName()->willReturn('medias');
        $otherMetadata = $this->prophesize(StructureMetadata::class);
        $otherMetadata->getName()->willReturn('other');
        $otherMetadata->hasPropertyWithTagName(Argument::cetera())->willReturn(true);
        $otherMetadata->getPropertyByTagName('sulu.teaser.description')->willReturn($otherTeaserDescriptionProperty->reveal());
        $otherMetadata->getPropertyByTagName('sulu.teaser.media')->willReturn($otherTeaserMediaProperty->reveal());

        $this->structureMetadataFactory->getStructures('page')->willReturn([
            $defaultMetadata->reveal(),
            $otherMetadata->reveal(),
        ]);

        $fooTeaserMediaId = 1;
        $fooTeaserMedia = $this->prophesize(Media::class);
        $fooTeaserMedia->getId()->willReturn($fooTeaserMediaId);

        $fooExcerptMediaId = 2;
        $fooExcerptMedia = $this->prophesize(Media::class);
        $fooExcerptMedia->getId()->willReturn($fooExcerptMediaId);

        $barTeaserMediaId = 3;
        $barTeaserMedia = $this->prophesize(Media::class);
        $barTeaserMedia->getId()->willReturn($barTeaserMediaId);

        $bazTeaserMediaId = 4;
        $bazTeaserMedia = $this->prophesize(Media::class);
        $bazTeaserMedia->getId()->willReturn($bazTeaserMediaId);

        $ids = ['abc', 'def', 'ghi'];
        $locale = 'en';

        $this->contentQueryBuilder->init(Argument::that(function($options) use ($ids) {
            $expectedOptions = \json_decode(\json_encode([
                'ids' => $ids,
                'properties' => [
                    new PropertyParameter('default_teaserDescription', 'description'),
                    new PropertyParameter('default_teaserMedia', 'image'),
                    new PropertyParameter('other_teaserDescription', 'article'),
                    new PropertyParameter('other_teaserMedia', 'medias'),
                    new PropertyParameter('excerptTitle', 'excerpt.title'),
                    new PropertyParameter('excerptDescription', 'excerpt.description'),
                    new PropertyParameter('excerptMore', 'excerpt.more'),
                    new PropertyParameter('excerptImages', 'excerpt.images'),
                ],
                'published' => true,
            ]), true);

            return $expectedOptions == \json_decode(\json_encode($options), true);
        }))->shouldBeCalled();

        $pagesData = [
            [
                'id' => $ids[0],
                'title' => 'Foo',
                'webspaceKey' => 'example',
                'template' => 'default',
                'url' => '/foo',
                'default_teaserDescription' => 'Teaser description of page foo',
                'default_teaserMedia' => $fooTeaserMedia->reveal(),
                'other_teaserDescription' => '',
                'other_teaserMedia' => '',
                'excerptTitle' => 'Excerpt title of page foo',
                'excerptDescription' => 'Excerpt description of page foo',
                'excerptMore' => 'Excerpt more of page foo',
                'excerptImages' => [
                    $fooExcerptMedia->reveal(),
                ],
            ],
            [
                'id' => $ids[1],
                'title' => 'Bar',
                'webspaceKey' => 'example',
                'template' => 'default',
                'url' => '/bar',
                'default_teaserDescription' => 'Teaser description of page bar',
                'default_teaserMedia' => $barTeaserMedia->reveal(),
                'other_teaserDescription' => '',
                'other_teaserMedia' => '',
                'excerptTitle' => '',
                'excerptDescription' => '',
                'excerptMore' => '',
                'excerptImages' => [],
            ],
            [
                'id' => $ids[2],
                'title' => 'Baz',
                'webspaceKey' => 'example',
                'template' => 'other',
                'url' => '/baz',
                'default_teaserDescription' => '',
                'default_teaserMedia' => '',
                'other_teaserDescription' => 'Teaser description of page baz',
                'other_teaserMedia' => [
                    $bazTeaserMedia->reveal(),
                ],
                'excerptTitle' => '',
                'excerptDescription' => '',
                'excerptMore' => '',
                'excerptImages' => [],
            ],
        ];

        $this->contentQueryExecutor->execute(
            null,
            [$locale],
            $this->contentQueryBuilder->reveal(),
            true,
            -1,
            null,
            null,
            false,
            64
        )->willReturn($pagesData);

        $teasers = $this->phpcrPageTeaserProvider->find($ids, $locale);

        $this->assertCount(3, $teasers);
        $this->assertTeaser(
            new Teaser(
                $pagesData[0]['id'],
                'pages',
                $locale,
                $pagesData[0]['excerptTitle'],
                $pagesData[0]['excerptDescription'],
                $pagesData[0]['excerptMore'],
                $pagesData[0]['url'],
                $fooExcerptMediaId,
                [
                    'structureType' => $pagesData[0]['template'],
                    'webspaceKey' => $pagesData[0]['webspaceKey'],
                ]
            ),
            $teasers[0]
        );
        $this->assertTeaser(
            new Teaser(
                $pagesData[1]['id'],
                'pages',
                $locale,
                $pagesData[1]['title'],
                $pagesData[1]['default_teaserDescription'],
                $pagesData[1]['excerptMore'],
                $pagesData[1]['url'],
                $barTeaserMediaId,
                [
                    'structureType' => $pagesData[1]['template'],
                    'webspaceKey' => $pagesData[1]['webspaceKey'],
                ]
            ),
            $teasers[1]
        );
        $this->assertTeaser(
            new Teaser(
                $pagesData[2]['id'],
                'pages',
                $locale,
                $pagesData[2]['title'],
                $pagesData[2]['other_teaserDescription'],
                $pagesData[2]['excerptMore'],
                $pagesData[2]['url'],
                $bazTeaserMediaId,
                [
                    'structureType' => $pagesData[2]['template'],
                    'webspaceKey' => $pagesData[2]['webspaceKey'],
                ]
            ),
            $teasers[2]
        );
    }

    private function assertTeaser(Teaser $expected, Teaser $teaser): void
    {
        $this->assertEquals($expected->getId(), $teaser->getId());
        $this->assertEquals($expected->getType(), $teaser->getType());
        $this->assertEquals($expected->getLocale(), $teaser->getLocale());
        $this->assertEquals($expected->getTitle(), $teaser->getTitle());
        $this->assertEquals($expected->getDescription(), $teaser->getDescription());
        $this->assertEquals($expected->getMoreText(), $teaser->getMoreText());
        $this->assertEquals($expected->getMediaId(), $teaser->getMediaId());
        $this->assertEquals($expected->getUrl(), $teaser->getUrl());
        $this->assertEquals($expected->getAttributes(), $teaser->getAttributes());
    }
}
