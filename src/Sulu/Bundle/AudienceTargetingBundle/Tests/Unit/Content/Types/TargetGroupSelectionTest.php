<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as PHPCRPropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Content\Types\TargetGroupSelection;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class TargetGroupSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TargetGroupRepositoryInterface>
     */
    private $targetGroupRepository;

    /**
     * @var TargetGroupSelection
     */
    private $audienceTargetingGroups;

    public function setUp(): void
    {
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->audienceTargetingGroups = new TargetGroupSelection($this->targetGroupRepository->reveal());
    }

    public function testRead(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('test');
        $property->setValue([1, 2])->shouldBeCalled();

        $node->getPropertyValueWithDefault('test', [])->willReturn([1, 2]);

        $this->audienceTargetingGroups->read($node->reveal(), $property->reveal(), 'sulu_io', 'en', null);
    }

    public function testGetContentDataEmpty(): void
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getValue()->willReturn([]);

        $this->targetGroupRepository->findByIds(Argument::any())->shouldNotBeCalled();
        $contentData = $this->audienceTargetingGroups->getContentData($property->reveal());

        $this->assertEquals([], $contentData);
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getValue()->willReturn([1, 2]);

        $targetGroup1 = new TargetGroup();
        $targetGroup2 = new TargetGroup();
        $this->targetGroupRepository->findByIds([1, 2])->willReturn([$targetGroup1, $targetGroup2]);

        $contentData = $this->audienceTargetingGroups->getContentData($property->reveal());

        $this->assertSame([$targetGroup1, $targetGroup2], $contentData);
    }

    public function testWrite(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->getValue()->willReturn([1, 2]);

        $node->setProperty('test', [1, 2])->shouldBeCalled();

        $this->audienceTargetingGroups->write($node->reveal(), $property->reveal(), 1, 'sulu_io', 'en', null);
    }

    public function testRemove(): void
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

    public function testRemoveNotExisting(): void
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

    public function testExportData(): void
    {
        $this->assertEquals('[]', $this->audienceTargetingGroups->exportData(null));
        $this->assertEquals('[]', $this->audienceTargetingGroups->exportData([]));
        $this->assertEquals('[1]', $this->audienceTargetingGroups->exportData([1]));
    }

    public function testImportDataEmpty(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('test');

        $property->setValue([])->shouldBeCalled();
        $property->getValue()->willReturn([]);
        $node->setProperty('test', [])->shouldBeCalled();

        $this->audienceTargetingGroups->importData($node->reveal(), $property->reveal(), '[]', 1, 'sulu_io', 'en');
    }

    public function testImportData(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('test');

        $property->setValue([1, 2])->shouldBeCalled();
        $property->getValue()->willReturn([1, 2]);
        $node->setProperty('test', [1, 2])->shouldBeCalled();

        $this->audienceTargetingGroups->importData($node->reveal(), $property->reveal(), '[1, 2]', 1, 'sulu_io', 'en');
    }
}
