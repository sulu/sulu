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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\ContentMerger\Merger\AuditableMerger;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AuditableMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getAuditableMergerInstance(): MergerInterface
    {
        return new AuditableMerger();
    }

    public function testMergeSourceNotImplementAuditableInterface(): void
    {
        $merger = $this->getAuditableMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuditableInterface::class);
        $target->setChanger(Argument::any())->shouldNotBeCalled();
        $target->setChanged(Argument::any())->shouldNotBeCalled();
        $target->setCreated(Argument::any())->shouldNotBeCalled();
        $target->setCreator(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementAuditableInterface(): void
    {
        $merger = $this->getAuditableMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuditableInterface::class);
        $source->getChanger()->shouldNotBeCalled();
        $source->getChanged()->shouldNotBeCalled();
        $source->getCreator()->shouldNotBeCalled();
        $source->getCreated()->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getAuditableMergerInstance();

        $changer = $this->prophesize(UserInterface::class);
        $creator = $this->prophesize(UserInterface::class);
        $changed = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');
        $created = new \DateTimeImmutable('2021-05-08T00:00:00+00:00');

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuditableInterface::class);
        $source->getCreator()->willReturn($creator->reveal())->shouldBeCalled();
        $source->getChanger()->willReturn($changer->reveal())->shouldBeCalled();
        $source->getCreated()->willReturn($created)->shouldBeCalled();
        $source->getChanged()->willReturn($changed)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuditableInterface::class);
        $target->setChanger($changer->reveal())->shouldBeCalled();
        $target->setChanged($changed)->shouldBeCalled();
        $target->setCreated($created)->shouldBeCalled();
        $target->setCreator($creator->reveal())->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getAuditableMergerInstance();

        $changed = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');
        $created = new \DateTimeImmutable('2021-05-08T00:00:00+00:00');

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuditableInterface::class);
        $source->getCreator()->willReturn(null)->shouldBeCalled();
        $source->getChanger()->willReturn(null)->shouldBeCalled();
        $source->getCreated()->willReturn($created)->shouldBeCalled();
        $source->getChanged()->willReturn($changed)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuditableInterface::class);
        $target->setChanger(Argument::any())->shouldNotBeCalled();
        $target->setChanged($changed)->shouldBeCalled()->willReturn($target->reveal());
        $target->setCreated($created)->shouldBeCalled()->willReturn($target->reveal());
        $target->setCreator(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
