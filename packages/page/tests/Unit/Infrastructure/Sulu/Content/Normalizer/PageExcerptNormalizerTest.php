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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content\Normalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\Normalizer\PageExcerptNormalizer;

class PageExcerptNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createPageExcerptNormalizerInstance(): PageExcerptNormalizer
    {
        return new PageExcerptNormalizer();
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();
        $object = $this->prophesize(TaxonomyInterface::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementExcerptInterface(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'excerptTagNames' => '12345',
            'excerptCategoryIds' => '123',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceNotImplementDimensionContentInterface(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();
        $object = $this->prophesize(TaxonomyInterface::class);

        $data = [
            'excerptTagNames' => '12345',
            'excerptCategoryIds' => '123',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceWithNonPageResource(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();

        $resource = $this->prophesize(ContentRichEntityInterface::class);

        $object = $this->prophesize(TaxonomyInterface::class);
        $object->willImplement(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());

        $data = [
            'excerptTagNames' => ['Tag 1', 'Tag 2'],
            'excerptCategoryIds' => [3, 4],
        ];

        $expectedResult = [
            'excerptTagNames' => ['Tag 1', 'Tag 2'],
            'excerptCategoryIds' => [3, 4],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceWithSegmentForPageResource(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();

        $page = $this->prophesize(PageInterface::class);
        $page->getWebspaceKey()->willReturn('example-webspace');

        $object = $this->prophesize(TaxonomyInterface::class);
        $object->willImplement(DimensionContentInterface::class);
        $object->getResource()->willReturn($page->reveal());
        $object->getExcerptSegment()->willReturn('test-segment');

        $data = [
            'excerptTagNames' => [],
            'excerptCategoryIds' => [],
        ];

        $expectedResult = [
            'excerptTagNames' => [],
            'excerptCategoryIds' => [],
            'excerptSegment' => [
                'example-webspace' => 'test-segment',
            ],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceWithNullSegmentForPageResource(): void
    {
        $normalizer = $this->createPageExcerptNormalizerInstance();

        $page = $this->prophesize(PageInterface::class);

        $object = $this->prophesize(TaxonomyInterface::class);
        $object->willImplement(DimensionContentInterface::class);
        $object->getResource()->willReturn($page->reveal());
        $object->getExcerptSegment()->willReturn(null);

        $data = [
            'excerptTagNames' => [],
            'excerptCategoryIds' => [],
        ];

        $expectedResult = [
            'excerptTagNames' => [],
            'excerptCategoryIds' => [],
            'excerptSegment' => null,
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
