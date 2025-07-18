<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Sulu\Content\Merger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Infrastructure\Sulu\Content\Merger\NavigationContextMerger;

class NavigationContextMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getMergerInstance(): MergerInterface
    {
        return new NavigationContextMerger();
    }

    public function testMergeSourceNotInstanceOfPageDimensionContentInterface(): void
    {
        $merger = $this->getMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(PageDimensionContentInterface::class);
        $target->setNavigationContexts(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotInstanceOfPageDimensionContentInterface(): void
    {
        $merger = $this->getMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(PageDimensionContentInterface::class);
        $source->getNavigationContexts()->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getMergerInstance();

        $page = new Page();
        $source = new PageDimensionContent($page);
        $source->setNavigationContexts(['main']);

        $target = new PageDimensionContent($page);
        self::assertEmpty($target->getNavigationContexts());
        $merger->merge($target, $source);

        self::assertSame(['main'], $target->getNavigationContexts());
    }

    public function testMergeEqualsNotSet(): void
    {
        $merger = $this->getMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(PageDimensionContentInterface::class);
        $source->getNavigationContexts()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(PageDimensionContentInterface::class);
        $target->getNavigationContexts()->willReturn([])->shouldBeCalled();
        $target->setNavigationContexts(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
