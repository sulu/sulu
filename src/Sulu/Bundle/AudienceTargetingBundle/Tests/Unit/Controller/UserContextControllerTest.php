<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit;

use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\Controller\UserContextController;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
use Symfony\Component\HttpFoundation\Request;

class UserContextControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var UserContextStoreInterface
     */
    private $userContextStore;

    public function setUp()
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->userContextStore = $this->prophesize(UserContextStoreInterface::class);
    }

    /**
     * @dataProvider provideTargetGroup
     */
    public function testTargetGroupAction($header, $currentTargetGroup, $targetGroup, $targetGroupId)
    {
        if ($currentTargetGroup) {
            $this->targetGroupRepository->find($currentTargetGroup->getId())->willReturn($currentTargetGroup);
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_SESSION, $currentTargetGroup)
                ->willReturn($targetGroup);
        } else {
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_USER, null)
                ->willReturn($targetGroup);
        }

        $userContextController = new UserContextController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->userContextStore->reveal(),
            $header
        );

        $request = new Request();
        if ($currentTargetGroup) {
            $request->headers->set($header, $currentTargetGroup->getId());
        }

        $response = $userContextController->targetGroupAction($request);

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
            ['X-User-Context-Hash', null, $targetGroup1->reveal(), null],
            ['X-User-Context-Hash', null, $targetGroup2->reveal(), 2],
            ['X-User-Context', null, null, 0],
            ['X-User-Context', $targetGroup3->reveal(), $targetGroup4->reveal(), 4],
        ];
    }

    /**
     * @dataProvider provideTargetGroupHit
     */
    public function testTargetGroupHitAction($oldTargetGroup, $newTargetGroup, $newTargetGroupId)
    {
        $this->targetGroupRepository->find($oldTargetGroup->getId())->willReturn($oldTargetGroup);
        $this->targetGroupEvaluator->evaluate(
            TargetGroupRuleInterface::FREQUENCY_HIT,
            $oldTargetGroup
        )->willReturn($newTargetGroup);
        $this->userContextStore->getUserContext()->willReturn($oldTargetGroup->getId());
        $userContextController = new UserContextController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->userContextStore->reveal(),
            'X-User-Context'
        );

        if ($newTargetGroupId) {
            $this->userContextStore->updateUserContext($newTargetGroupId)->shouldBeCalled();
        } else {
            $this->userContextStore->updateUserContext(Argument::any())->shouldNotBeCalled();
        }

        $userContextController->targetGroupHitAction();
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
