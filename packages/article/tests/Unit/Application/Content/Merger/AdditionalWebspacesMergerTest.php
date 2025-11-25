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

namespace Sulu\Article\Tests\Unit\Application\Content\Merger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Infrastructure\Sulu\Content\Merger\AdditionalWebspacesMerger;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getAdditionalWebspacesMergerInstance(): MergerInterface
    {
        return new AdditionalWebspacesMerger();
    }

    public function testMergeCustomizeWebspaceSettingsSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $customizeWebspaceSettings = true;

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ArticleDimensionContentInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn($customizeWebspaceSettings)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn(['test-webspace'])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ArticleDimensionContentInterface::class);
        $target->setCustomizeWebspaceSettings($customizeWebspaceSettings)->shouldBeCalled()->willReturn($target->reveal());
        $target->setAdditionalWebspaces(['test-webspace'])->shouldBeCalled()->willReturn($target->reveal());

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeCustomizeWebspaceSettingsNotSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ArticleDimensionContentInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(false)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ArticleDimensionContentInterface::class);
        $target->setCustomizeWebspaceSettings(Argument::any())->shouldNotBeCalled();
        $target->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($target->reveal());

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $additionalWebspaces = ['sulu-io', 'example-com'];

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ArticleDimensionContentInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn($additionalWebspaces)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ArticleDimensionContentInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($target->reveal());
        $target->setAdditionalWebspaces($additionalWebspaces)->shouldBeCalled()->willReturn($target->reveal());

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesNotSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ArticleDimensionContentInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ArticleDimensionContentInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($target->reveal());
        $target->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($target->reveal());

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesEmpty(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $additionalWebspaces = [];

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ArticleDimensionContentInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn($additionalWebspaces)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ArticleDimensionContentInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($target->reveal());
        $target->setAdditionalWebspaces($additionalWebspaces)->shouldBeCalled()->willReturn($target->reveal());

        $merger->merge($target->reveal(), $source->reveal());
    }
}
