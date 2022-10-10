<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Controller\TargetGroupEvaluationController;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Symfony\Component\HttpFoundation\Request;

class TargetGroupEvaluationControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TargetGroupEvaluatorInterface>
     */
    private $targetGroupEvaluator;

    /**
     * @var ObjectProphecy<TargetGroupRepositoryInterface>
     */
    private $targetGroupRepository;

    /**
     * @var ObjectProphecy<TargetGroupStoreInterface>
     */
    private $targetGroupStore;

    public function setUp(): void
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
    }

    /**
     * @dataProvider provideTargetGroup
     */
    public function testTargetGroupAction($header, $currentTargetGroup, $targetGroup, $targetGroupId): void
    {
        if ($currentTargetGroup) {
            $this->targetGroupRepository->find($currentTargetGroup->getId())->willReturn($currentTargetGroup);
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_SESSION, $currentTargetGroup)
                ->willReturn($targetGroup);
        } else {
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_VISITOR, null)
                ->willReturn($targetGroup);
        }

        $targetGroupEvaluationController = new TargetGroupEvaluationController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->targetGroupStore->reveal(),
            $header
        );

        $request = new Request();
        if ($currentTargetGroup) {
            $request->headers->set($header, $currentTargetGroup->getId());
        }

        $response = $targetGroupEvaluationController->targetGroupAction($request);

        $this->assertEquals($targetGroupId, $response->headers->get($header));
    }

    public function provideTargetGroup()
    {
        $targetGroup1 = $this->prophesize(TargetGroupInterface::class);

        $targetGroup2 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup2->getId()->willReturn(2);

        $targetGroup3 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup3->getId()->willReturn(3);
        $targetGroup4 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup4->getId()->willReturn(4);

        return [
            ['X-Sulu-Target-Group-Hash', null, $targetGroup1->reveal(), null],
            ['X-Sulu-Target-Group-Hash', null, $targetGroup2->reveal(), 2],
            ['X-Sulu-Target-Group', null, null, 0],
            ['X-Sulu-Target-Group', $targetGroup3->reveal(), $targetGroup4->reveal(), 4],
        ];
    }

    /**
     * @dataProvider provideTargetGroupHit
     */
    public function testTargetGroupHitAction($oldTargetGroup, $newTargetGroup, $newTargetGroupId): void
    {
        $this->targetGroupRepository->find($oldTargetGroup->getId())->willReturn($oldTargetGroup);
        $this->targetGroupEvaluator->evaluate(
            TargetGroupRuleInterface::FREQUENCY_HIT,
            $oldTargetGroup
        )->willReturn($newTargetGroup);
        $this->targetGroupStore->getTargetGroupId(true)->willReturn($oldTargetGroup->getId());
        $targetGroupEvaluationController = new TargetGroupEvaluationController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->targetGroupStore->reveal(),
            'X-Sulu-Target-Group'
        );

        if ($newTargetGroupId) {
            $this->targetGroupStore->updateTargetGroupId($newTargetGroupId)->shouldBeCalled();
        } else {
            $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();
        }

        $targetGroupEvaluationController->targetGroupHitAction();
    }

    public function provideTargetGroupHit()
    {
        $oldTargetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $oldTargetGroup1->getId()->willReturn(1);
        $newTargetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $newTargetGroup1->getId()->willReturn(2);

        return [
            [$oldTargetGroup1->reveal(), $newTargetGroup1->reveal(), 2],
            [$oldTargetGroup1->reveal(), null, null],
        ];
    }
}
