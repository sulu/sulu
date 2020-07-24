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
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Component\Content\Compat\PropertyInterface;

class SegmentSelectTest extends TestCase
{
    public function testRead()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(true);
        $node->getPropertyValue('test')->willReturn('{"sulu_io":"w"}');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue(['sulu_io' => 'w'])->shouldBeCalled();

        $segmentSelect = new SegmentSelect();

        $segmentSelect->read($node->reveal(), $property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadPropertyNotExists()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(false);
        $node->getPropertyValue('test')->shouldNotBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue([])->shouldBeCalled();

        $segmentSelect = new SegmentSelect();

        $segmentSelect->read($node->reveal(), $property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test', '{"sulu_io":"w","other":"a"}')->shouldBeCalled();
        $node->setProperty('test-sulu_io', 'w')->shouldBeCalled();
        $node->setProperty('test-other', 'a')->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['sulu_io' => 'w', 'other' => 'a']);
        $property->getName()->willReturn('test');

        $segmentSelect = new SegmentSelect();

        $segmentSelect->write($node->reveal(), $property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testExportData()
    {
        $segmentSelect = new SegmentSelect();

        $exportResult = $segmentSelect->exportData(['sulu_io' => 'w', 'other' => 'a']);

        $this->assertSame('{"sulu_io":"w","other":"a"}', $exportResult);
    }

    public function testImportData()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test', '{"sulu_io":"w","other":"a"}')->shouldBeCalled();
        $node->setProperty('test-sulu_io', 'w')->shouldBeCalled();
        $node->setProperty('test-other', 'a')->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->setValue(['sulu_io' => 'w', 'other' => 'a'])->shouldBeCalled();
        $property->getValue()->willReturn(['sulu_io' => 'w', 'other' => 'a']);
        $property->getName()->willReturn('test');

        $segmentSelect = new SegmentSelect();

        $segmentSelect->importData(
            $node->reveal(),
            $property->reveal(),
            '{"sulu_io":"w","other":"a"}',
            1,
            'sulu_io',
            'de',
            null
        );
    }
}
