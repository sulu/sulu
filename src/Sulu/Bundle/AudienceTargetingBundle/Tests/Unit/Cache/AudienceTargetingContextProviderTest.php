<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Cache;

use FOS\HttpCache\UserContext\UserContext;
use Sulu\Bundle\AudienceTargetingBundle\Cache\AudienceTargetingContextProvider;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;

class AudienceTargetingContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var AudienceTargetingContextProvider
     */
    private $audienceTargetingContextProvider;

    public function setUp()
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);

        $this->audienceTargetingContextProvider = new AudienceTargetingContextProvider(
            $this->targetGroupEvaluator->reveal()
        );
    }

    public function testAddUserContext()
    {
        $userContext = new UserContext();

        $targetGroup = new TargetGroup();
        $this->targetGroupEvaluator->evaluate()->willReturn($targetGroup);

        $this->audienceTargetingContextProvider->updateUserContext($userContext);

        $this->assertEquals(['audience-targeting' => $targetGroup], $userContext->getParameters());
    }
}
