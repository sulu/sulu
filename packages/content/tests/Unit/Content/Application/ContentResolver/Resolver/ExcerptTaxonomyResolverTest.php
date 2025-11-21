<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentResolver\Resolver\ExcerptTaxonomyResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExcerptTaxonomyResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveWithNonExcerptInterface(): void
    {
        $resolver = new ExcerptTaxonomyResolver(
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal(),
        );

        self::assertNull($resolver->resolve($this->prophesize(DimensionContentInterface::class)->reveal()));
    }

    public function testResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $dimensionContent->setExcerptTitle('Sulu');
        $dimensionContent->setExcerptDescription('Sulu is awesome');
        $dimensionContent->setExcerptMore('Sulu is more awesome');
        $dimensionContent->setExcerptIcon(['id' => 1]);
        $dimensionContent->setExcerptImage(['id' => 2]);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_excerpt', 'en', ['instanceOf' => ExampleDimensionContent::class])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems([], [
            'excerptTitle' => 'Sulu',
            'excerptDescription' => 'Sulu is awesome',
            'excerptMore' => 'Sulu is more awesome',
            'excerptIcon' => ['id' => 1],
            'excerptImage' => ['id' => 2],
            'excerptSegment' => null,
            'excerptCategories' => [],
            'excerptTags' => [],
            'excerptAudienceTargetGroups' => [],
        ], 'en')
            ->willReturn(
                [
                    ContentView::create(['dummy' => 'data'], []),
                ]
            );

        $resolver = new ExcerptTaxonomyResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal(),
        );

        $contentView = $resolver->resolve($dimensionContent);

        self::assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertCount(1, $content);
    }

    public function testResolveWithTaxonomy(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $tag1 = $this->prophesize(TagInterface::class);
        $tag1->getName()->willReturn('tag1');
        $tag2 = $this->prophesize(TagInterface::class);
        $tag2->getName()->willReturn('tag2');

        $category1 = $this->prophesize(CategoryInterface::class);
        $category1->getId()->willReturn(1);
        $category2 = $this->prophesize(CategoryInterface::class);
        $category2->getId()->willReturn(2);

        $dimensionContent->setExcerptTitle('Sulu');
        $dimensionContent->setExcerptDescription('Sulu is awesome');
        $dimensionContent->setExcerptMore('Sulu is more awesome');
        $dimensionContent->setExcerptIcon(['id' => 3]);
        $dimensionContent->setExcerptImage(['id' => 4]);
        $dimensionContent->setExcerptTags([$tag1->reveal(), $tag2->reveal()]);
        $dimensionContent->setExcerptCategories([$category1->reveal(), $category2->reveal()]);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_excerpt', 'en', ['instanceOf' => ExampleDimensionContent::class])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems([], [
            'excerptTitle' => 'Sulu',
            'excerptDescription' => 'Sulu is awesome',
            'excerptMore' => 'Sulu is more awesome',
            'excerptIcon' => ['id' => 3],
            'excerptImage' => ['id' => 4],
            'excerptSegment' => null,
            'excerptCategories' => [1, 2],
            'excerptTags' => ['tag1', 'tag2'],
            'excerptAudienceTargetGroups' => [],
        ], 'en')
            ->willReturn(
                [
                    ContentView::create(['dummy' => 'data'], []),
                ]
            );

        $resolver = new ExcerptTaxonomyResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal(),
        );

        $contentView = $resolver->resolve($dimensionContent);

        self::assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertCount(1, $content);
    }
}
