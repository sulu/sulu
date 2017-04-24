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
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;

class UserContextControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    public function setUp()
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
    }

    /**
     * @dataProvider provideConfiguration
     */
    public function testHashAction($header, $targetGroup, $targetGroupId)
    {
        $this->targetGroupEvaluator->evaluate()->willReturn($targetGroup);
        $userContextController = new UserContextController($this->targetGroupEvaluator->reveal(), $header);
        $response = $userContextController->targetGroupAction();

        $this->assertEquals($targetGroupId, $response->headers->get($header));
    }

    public function provideConfiguration()
    {
        $targetGroup = new TargetGroup();

        return [
            ['X-User-Context-Hash', $targetGroup, null],
            ['X-User-Context', null, 0],
        ];
    }
}
