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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Content\Application\ContentMerger\Merger\ExcerptMerger;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

class ExcerptMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getExcerptMergerInstance(): MergerInterface
    {
        return new ExcerptMerger();
    }

    public function testMergeSourceNotImplementExcerptInterface(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->setExcerptData(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementExcerptInterface(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptData()->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptData()->willReturn([
            'title' => 'Excerpt Title',
            'description' => 'Excerpt Description',
            'more' => 'Excerpt More',
            'image' => ['id' => 8],
            'icon' => ['id' => 9],
        ])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->getExcerptData()->willReturn([])->shouldBeCalled();
        $target->setExcerptData([
            'title' => 'Excerpt Title',
            'description' => 'Excerpt Description',
            'more' => 'Excerpt More',
            'image' => ['id' => 8],
            'icon' => ['id' => 9],
        ])->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptData()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->getExcerptData()->willReturn([])->shouldBeCalled();
        $target->setExcerptData([])->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
