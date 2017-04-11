<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as PHPCRPropertyInterface;
use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\Content\Types\AudienceTargetingGroups;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class AudienceTargetingGroupsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var AudienceTargetingGroups
     */
    private $audienceTargetingGroups;

    public function setUp()
    {
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->audienceTargetingGroups = new AudienceTargetingGroups($this->targetGroupRepository->reveal());
    }

    public function testRead()
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('test');
        $property->setValue([1, 2])->shouldBeCalled();

        $node->getPropertyValueWithDefault('test', [])->willReturn([1, 2]);

        $this->audienceTargetingGroups->read($node->reveal(), $property->reveal(), 'sulu_io', 'en', null);
    }

    public function testGetContentDataEmpty()
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getValue()->willReturn([]);

        $this->targetGroupRepository->findByIds(Argument::any())->shouldNotBeCalled();
        $contentData = $this->audienceTargetingGroups->getContentData($property->reveal());

        $this->assertEquals([], $contentData);
    }

    public function testGetContentData()
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getValue()->willReturn([1, 2]);

        $targetGroup1 = new TargetGroup();
        $targetGroup2 = new TargetGroup();
        $this->targetGroupRepository->findByIds([1, 2])->willReturn([$targetGroup1, $targetGroup2]);

        $contentData = $this->audienceTargetingGroups->getContentData($property->reveal());

        $this->assertSame([$targetGroup1, $targetGroup2], $contentData);
    }

    public function testWrite()
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->getValue()->willReturn([1, 2]);

        $node->setProperty('test', [1, 2])->shouldBeCalled();

        $this->audienceTargetingGroups->write($node->reveal(), $property->reveal(), 1, 'sulu_io', 'en', null);
    }

    public function testRemove()
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $phpcrProperty = $this->prophesize(PHPCRPropertyInterface::class);

        $property->getName()->willReturn('test');
        $node->hasProperty('test')->willReturn(true);
        $node->getProperty('test')->willReturn($phpcrProperty);

        $phpcrProperty->remove()->shouldBeCalled();

        $this->audienceTargetingGroups->remove($node->reveal(), $property->reveal(), 'sulu_io', 'en', null);
    }

    public function testRemoveNotExisting()
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $phpcrProperty = $this->prophesize(PHPCRPropertyInterface::class);

        $property->getName()->willReturn('test');
        $node->hasProperty('test')->willReturn(false);
        $node->getProperty('test')->willReturn($phpcrProperty);

        $phpcrProperty->remove()->shouldNotBeCalled();

        $this->audienceTargetingGroups->remove($node->reveal(), $property->reveal(), 'sulu_io', 'en', null);
    }
}
