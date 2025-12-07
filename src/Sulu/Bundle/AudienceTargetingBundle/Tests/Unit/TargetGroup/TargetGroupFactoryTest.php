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

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\TargetGroup;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupFactory;

class TargetGroupFactoryTest extends TestCase
{
    use ProphecyTrait;

    private TargetGroupFactory $targetGroupFactory;
    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->targetGroupFactory = new TargetGroupFactory(
            $this->entityManager->reveal(),
        );
    }

    public function testCreateWithEmptyArray(): void
    {
        $result = $this->targetGroupFactory->create([]);
        $this->assertSame([], $result);
        $this->entityManager->getReference()->shouldNotHaveBeenCalled();
    }

    public function testCreateWithSingleTargetGroupId(): void
    {
        $targetGroupId = 1;
        $targetGroup = $this->prophesize(TargetGroupInterface::class);

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            $targetGroupId,
        )->willReturn($targetGroup->reveal())->shouldBeCalled();

        $result = $this->targetGroupFactory->create([$targetGroupId]);

        $this->assertCount(1, $result);
        $this->assertSame($targetGroup->reveal(), $result[0]);
    }

    public function testCreateWithMultipleTargetGroupIds(): void
    {
        $targetGroupIds = [1, 2, 3];
        $targetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup2 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup3 = $this->prophesize(TargetGroupInterface::class);

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            1,
        )->willReturn($targetGroup1->reveal())->shouldBeCalled();

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            2,
        )->willReturn($targetGroup2->reveal())->shouldBeCalled();

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            3,
        )->willReturn($targetGroup3->reveal())->shouldBeCalled();

        $result = $this->targetGroupFactory->create($targetGroupIds);

        $this->assertCount(3, $result);
        $this->assertSame($targetGroup1->reveal(), $result[0]);
        $this->assertSame($targetGroup2->reveal(), $result[1]);
        $this->assertSame($targetGroup3->reveal(), $result[2]);
    }

    public function testCreatePreservesOrder(): void
    {
        $targetGroupIds = [5, 1, 3];
        $targetGroup5 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup3 = $this->prophesize(TargetGroupInterface::class);

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            5,
        )->willReturn($targetGroup5->reveal())->shouldBeCalled();

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            1,
        )->willReturn($targetGroup1->reveal())->shouldBeCalled();

        $this->entityManager->getReference(
            TargetGroupInterface::class,
            3,
        )->willReturn($targetGroup3->reveal())->shouldBeCalled();

        $result = $this->targetGroupFactory->create($targetGroupIds);

        $this->assertCount(3, $result);
        $this->assertSame($targetGroup5->reveal(), $result[0]);
        $this->assertSame($targetGroup1->reveal(), $result[1]);
        $this->assertSame($targetGroup3->reveal(), $result[2]);
    }
}
