<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor\TargetGroupBlockVisitor;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Component\Content\Compat\Metadata;

class TargetGroupBlockVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TargetGroupStoreInterface>
     */
    private $targetGroupStore;

    /**
     * @var TargetGroupBlockVisitor
     */
    private $targetGroupBlockVisitor;

    public function setUp(): void
    {
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
        $this->targetGroupBlockVisitor = new TargetGroupBlockVisitor($this->targetGroupStore->reveal());
    }

    public function testShouldNotSkipWithObjectAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => new \stdClass()];

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => []];

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldSkipWithOtherTargetGroup(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['target_groups_enabled' => true, 'target_groups' => [1, 2]]];

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertNull($this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithSameTargetGroup(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['target_groups_enabled' => true, 'target_groups' => [1, 2, 3]]];

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithoutTargetGroups(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['target_groups_enabled' => true]];

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithDisabledTargetGroups(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['target_groups_enabled' => false, 'target_groups' => [1, 2]]];

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithoutTargetGroupsFlag(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['target_groups' => [1, 2]]];

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($block, $this->targetGroupBlockVisitor->visit($block));
    }
}
