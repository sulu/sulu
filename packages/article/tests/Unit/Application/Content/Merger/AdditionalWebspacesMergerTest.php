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
use Sulu\Article\Application\Content\Merger\AdditionalWebspacesMerger;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getAdditionalWebspacesMergerInstance(): MergerInterface
    {
        return new AdditionalWebspacesMerger();
    }

    public function testMergeSourceNotImplementAdditionalWebspacesInterface(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings(Argument::any())->shouldNotBeCalled();
        $target->setAdditionalWebspaces(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementAdditionalWebspacesInterface(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->setCustomizeWebspaceSettings(Argument::any())->shouldNotBeCalled();
        $source->setAdditionalWebspaces(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeCustomizeWebspaceSettingsSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $customizeWebspaceSettings = true;

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn($customizeWebspaceSettings)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings($customizeWebspaceSettings)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeCustomizeWebspaceSettingsNotSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(false)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $additionalWebspaces = ['sulu-io', 'example-com'];

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn($additionalWebspaces)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $target->setAdditionalWebspaces($additionalWebspaces)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesNotSet(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn(null)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $target->setAdditionalWebspaces(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeAdditionalWebspacesEmpty(): void
    {
        $merger = $this->getAdditionalWebspacesMergerInstance();

        $additionalWebspaces = [];

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AdditionalWebspacesInterface::class);
        $source->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();
        $source->getAdditionalWebspaces()->willReturn($additionalWebspaces)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AdditionalWebspacesInterface::class);
        $target->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $target->setAdditionalWebspaces($additionalWebspaces)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}