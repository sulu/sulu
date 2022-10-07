<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStore;

class TargetGroupStoreTest extends TestCase
{
    /**
     * @var TargetGroupStore
     */
    private $targetGroupStore;

    public function setUp(): void
    {
        $this->targetGroupStore = new TargetGroupStore();
    }

    public function testSetTargetGroupId(): void
    {
        $this->targetGroupStore->setTargetGroupId('2');
        $this->assertEquals('2', $this->targetGroupStore->getTargetGroupId());
        $this->assertEquals(true, $this->targetGroupStore->hasInfluencedContent());
    }

    public function testGetTargetGroupIdInternal(): void
    {
        $this->targetGroupStore->setTargetGroupId('2');
        $this->assertEquals('2', $this->targetGroupStore->getTargetGroupId(true));
        $this->assertEquals(false, $this->targetGroupStore->hasInfluencedContent());
    }

    public function testUpdateTargetGroupId(): void
    {
        $this->targetGroupStore->setTargetGroupId('2');
        $this->assertEquals('2', $this->targetGroupStore->getTargetGroupId());
        $this->assertFalse($this->targetGroupStore->hasChangedTargetGroup());

        $this->targetGroupStore->updateTargetGroupId('3');
        $this->assertEquals('3', $this->targetGroupStore->getTargetGroupId());
        $this->assertTrue($this->targetGroupStore->hasChangedTargetGroup());
    }

    public function testChangeTargetGroupIdToSame(): void
    {
        $this->targetGroupStore->setTargetGroupId('2');
        $this->targetGroupStore->updateTargetGroupId('2');
        $this->assertEquals('2', $this->targetGroupStore->getTargetGroupId());
        $this->assertFalse($this->targetGroupStore->hasChangedTargetGroup());
    }

    public function testChangeTargetGroupIdToSameDifferentType(): void
    {
        $this->targetGroupStore->setTargetGroupId('2');
        $this->targetGroupStore->updateTargetGroupId(2);
        $this->assertEquals('2', $this->targetGroupStore->getTargetGroupId());
        $this->assertFalse($this->targetGroupStore->hasChangedTargetGroup());
    }
}
