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
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Application\ContentMerger\Merger\SeoMerger;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;

class SeoMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getSeoMergerInstance(): MergerInterface
    {
        return new SeoMerger();
    }

    public function testMergeSourceNotImplementSeoInterface(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->setSeoData(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementSeoInterface(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoData()->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoData()->willReturn([
            'title' => 'Seo Title',
            'description' => 'Seo Description',
            'keywords' => 'Seo Keyword 1, Seo Keyword 2',
            'canonicalUrl' => 'https://canonical.localhost/',
        ])->shouldBeCalled();
        $source->getSeoNoFollow()->willReturn(true)->shouldBeCalled();
        $source->getSeoNoIndex()->willReturn(true)->shouldBeCalled();
        $source->getSeoHideInSitemap()->willReturn(true)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->getSeoData()->willReturn([])->shouldBeCalled();
        $target->setSeoData([
            'title' => 'Seo Title',
            'description' => 'Seo Description',
            'keywords' => 'Seo Keyword 1, Seo Keyword 2',
            'canonicalUrl' => 'https://canonical.localhost/',
        ])->shouldBeCalled();
        $target->setSeoNoFollow(true)->shouldBeCalled();
        $target->setSeoNoIndex(true)->shouldBeCalled();
        $target->setSeoHideInSitemap(true)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $seoMerger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoData()->willReturn([])->shouldBeCalled();
        $source->getSeoNoFollow()->willReturn(false)->shouldBeCalled();
        $source->getSeoNoIndex()->willReturn(false)->shouldBeCalled();
        $source->getSeoHideInSitemap()->willReturn(false)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->getSeoData()->willReturn([])->shouldBeCalled();
        $target->setSeoData([])->shouldBeCalled();
        $target->setSeoNoFollow(false)->shouldBeCalled();
        $target->setSeoNoIndex(false)->shouldBeCalled();
        $target->setSeoHideInSitemap(false)->shouldBeCalled();

        $seoMerger->merge($target->reveal(), $source->reveal());
    }
}
