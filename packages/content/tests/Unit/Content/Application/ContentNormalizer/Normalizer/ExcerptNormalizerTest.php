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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentNormalizer\Normalizer\ExcerptNormalizer;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

class ExcerptNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createExcerptNormalizerInstance(): ExcerptNormalizer
    {
        return new ExcerptNormalizer();
    }

    public function testIgnoredAttributesNotImplementExcerptInterface(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(ExcerptInterface::class);

        $this->assertSame(
            [
                'excerptTags',
                'excerptCategories',
                'excerptAudienceTargetGroups',
            ],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementExcerptInterface(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
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

    public function testEnhance(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();

        $resource = $this->prophesize(ContentRichEntityInterface::class);

        $object = $this->prophesize(ExcerptInterface::class);
        $object->willImplement(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());

        $data = [
            'excerptTagNames' => ['Tag 1', 'Tag 2'],
            'excerptCategoryIds' => [3, 4],
        ];

        $expectedResult = [
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
            'excerptAudienceTargetGroups' => [],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceWithAudienceTargetGroups(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();

        $resource = $this->prophesize(ContentRichEntityInterface::class);

        $object = $this->prophesize(ExcerptInterface::class);
        $object->willImplement(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());

        $data = [
            'excerptTagNames' => ['Tag 1', 'Tag 2'],
            'excerptCategoryIds' => [3, 4],
            'excerptAudienceTargetGroupIds' => [5, 6, 7],
        ];

        $expectedResult = [
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
            'excerptAudienceTargetGroups' => [5, 6, 7],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
