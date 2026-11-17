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

namespace Sulu\Article\Tests\Unit\Application\Content\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Infrastructure\Sulu\Content\DataMapper\ExcerptPriorityDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ExcerptPriorityDataMapperTest extends TestCase
{
    use ProphecyTrait;

    private function getDataMapper(): ExcerptPriorityDataMapper
    {
        return new ExcerptPriorityDataMapper();
    }

    public function testMapNotImplementingInterface(): void
    {
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->getDataMapper()->map(
            $unlocalizedDimensionContent->reveal(),
            $localizedDimensionContent->reveal(),
            ['excerptPriority' => 5],
        );

        // No setter should be called when the dimension content is not an article.
        $this->expectNotToPerformAssertions();
    }

    public function testMapSetsExcerptPriority(): void
    {
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $localizedDimensionContent->setExcerptPriority(5)->shouldBeCalled()->willReturn($localizedDimensionContent->reveal());

        $this->getDataMapper()->map(
            $unlocalizedDimensionContent->reveal(),
            $localizedDimensionContent->reveal(),
            ['excerptPriority' => 5],
        );
    }

    public function testMapCastsNumericStringToInt(): void
    {
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $localizedDimensionContent->setExcerptPriority(7)->shouldBeCalled()->willReturn($localizedDimensionContent->reveal());

        $this->getDataMapper()->map(
            $unlocalizedDimensionContent->reveal(),
            $localizedDimensionContent->reveal(),
            ['excerptPriority' => '7'],
        );
    }

    public function testMapNonNumericValueDefaultsToZero(): void
    {
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $localizedDimensionContent->setExcerptPriority(0)->shouldBeCalled()->willReturn($localizedDimensionContent->reveal());

        $this->getDataMapper()->map(
            $unlocalizedDimensionContent->reveal(),
            $localizedDimensionContent->reveal(),
            ['excerptPriority' => 'not-a-number'],
        );
    }

    public function testMapMissingKeyDoesNothing(): void
    {
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $localizedDimensionContent->setExcerptPriority(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->getDataMapper()->map(
            $unlocalizedDimensionContent->reveal(),
            $localizedDimensionContent->reveal(),
            [],
        );
    }
}
