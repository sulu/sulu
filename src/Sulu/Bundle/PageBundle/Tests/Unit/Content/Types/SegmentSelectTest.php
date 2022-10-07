<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as PhpcrPropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Component\Content\Compat\PropertyInterface;

class SegmentSelectTest extends TestCase
{
    use ProphecyTrait;

    public function testRead(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue(['website' => 'w', 'blog' => 's'])->shouldBeCalled();

        $webspaceProperty1 = $this->prophesize(PropertyInterface::class);
        $webspaceProperty1->getName()->willReturn('test-website');
        $webspaceProperty1->getValue()->willReturn('w');

        $webspaceProperty2 = $this->prophesize(PropertyInterface::class);
        $webspaceProperty2->getName()->willReturn('test-blog');
        $webspaceProperty2->getValue()->willReturn('s');

        $node->getProperties('test-*')->willReturn([$webspaceProperty1->reveal(), $webspaceProperty2->reveal()]);
        $node->hasProperty('test')->willReturn(true);

        $segmentSelect = new SegmentSelect();

        $segmentSelect->read($node->reveal(), $property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadPropertyNotExists(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getProperties('test-*')->willReturn([]);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue([])->shouldBeCalled();

        $segmentSelect = new SegmentSelect();

        $segmentSelect->read($node->reveal(), $property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test-sulu_io', 'w')->shouldBeCalled();
        $node->setProperty('test-other', 'a')->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['sulu_io' => 'w', 'other' => 'a']);
        $property->getName()->willReturn('test');

        $suluIoProperty = $this->prophesize(PhpcrPropertyInterface::class);
        $suluIoProperty->getName()->willReturn('test-sulu_io');
        $otherProperty = $this->prophesize(PhpcrPropertyInterface::class);
        $otherProperty->getName()->willReturn('test-other');

        $node->getProperties('test-*')->willReturn([$suluIoProperty->reveal(), $otherProperty->reveal()]);

        $segmentSelect = new SegmentSelect();

        $segmentSelect->write($node->reveal(), $property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteWithRemovals(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test-other', 'a')->shouldBeCalled();
        $node->setProperty('test-sulu_blog', 's')->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['other' => 'a', 'sulu_blog' => 's']);
        $property->getName()->willReturn('test');

        $suluWebsiteProperty = $this->prophesize(PhpcrPropertyInterface::class);
        $suluWebsiteProperty->getName()->willReturn('test-sulu_website');
        $otherProperty = $this->prophesize(PhpcrPropertyInterface::class);
        $otherProperty->getName()->willReturn('test-other');
        $suluBlogProperty = $this->prophesize(PhpcrPropertyInterface::class);
        $suluBlogProperty->getName()->willReturn('test-sulu_blog');

        $node->getProperties('test-*')->willReturn(
            [$suluWebsiteProperty->reveal(), $otherProperty->reveal(), $suluBlogProperty->reveal()]
        );

        $suluWebsiteProperty->remove()->shouldBeCalled();
        $suluBlogProperty->remove()->shouldNotBeCalled();
        $otherProperty->remove()->shouldNotBeCalled();

        $segmentSelect = new SegmentSelect();

        $segmentSelect->write($node->reveal(), $property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testRemove(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $phpcrProperty1 = $this->prophesize(PhpcrPropertyInterface::class);
        $phpcrProperty2 = $this->prophesize(PhpcrPropertyInterface::class);
        $node->getProperties('property-*')->willReturn([$phpcrProperty1, $phpcrProperty2]);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property');

        $segmentSelect = new SegmentSelect();

        $segmentSelect->remove($node->reveal(), $property->reveal(), 'sulu_io', 'de', null);

        $phpcrProperty1->remove()->shouldBeCalled();
        $phpcrProperty2->remove()->shouldBeCalled();
    }
}
