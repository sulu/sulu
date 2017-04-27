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

use Sulu\Bundle\AudienceTargetingBundle\Controller\UserContextController;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
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

    public function setUp()
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
    }

    /**
     * @dataProvider provideTargetGroup
     */
    public function testTargetGroupAction($header, $targetGroup, $targetGroupId)
    {
        $this->targetGroupEvaluator->evaluate()->willReturn($targetGroup);
        $userContextController = new UserContextController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $header
        );
        $response = $userContextController->targetGroupAction();

        $this->assertEquals($targetGroupId, $response->headers->get($header));
    }

    public function provideTargetGroup()
    {
        $targetGroup = new TargetGroup();

        return [
            ['X-User-Context-Hash', $targetGroup, null],
            ['X-User-Context', null, 0],
        ];
    }

    /**
     * @dataProvider provideTargetGroupHit
     */
    public function testTargetGroupHitAction($oldTargetGroup, $newTargetGroup, $newTargetGroupId)
    {
        $request = new Request([], [], [], ['user-context' => (string) $oldTargetGroup->getId()]);
        $this->targetGroupRepository->find($oldTargetGroup->getId())->willReturn($oldTargetGroup);
        $this->targetGroupEvaluator->evaluate(
            TargetGroupRuleInterface::FREQUENCY_HIT,
            $oldTargetGroup
        )->willReturn($newTargetGroup);
        $userContextController = new UserContextController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            'X-User-Context'
        );
        $response = $userContextController->targetGroupHitAction($request);

        if ($newTargetGroupId) {
            $cookie = $response->headers->getCookies()[0];
            $this->assertEquals('user-context', $cookie->getName());
            $this->assertEquals($newTargetGroupId, $cookie->getValue());
        } else {
            $this->assertCount(0, $response->headers->getCookies());
        }
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
            [$oldTargetGroup1->reveal(), $oldTargetGroup1->reveal(), null],
        ];
    }
}
