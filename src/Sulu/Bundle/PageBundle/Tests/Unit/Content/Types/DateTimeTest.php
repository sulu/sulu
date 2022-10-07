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

use PHPCR\ItemInterface;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Content\Types\DateTime;
use Sulu\Component\Content\Compat\PropertyInterface;

class DateTimeTest extends TestCase
{
    use ProphecyTrait;

    public function testRead(): void
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';
        $dateValue = new \DateTime('2020-07-02T11:30:00');

        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(true);
        $node->getPropertyValue('test')->willReturn($dateValue);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue('2020-07-02T11:30:00')->shouldBeCalled();

        $dateTime = new DateTime();

        $dateTime->read($node->reveal(), $property->reveal(), $webspaceKey, $locale, null);
    }

    public function testReadPropertyNotExists(): void
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(false);
        $node->getPropertyValue('test')->shouldNotBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue('')->shouldBeCalled();

        $dateTime = new DateTime();

        $dateTime->read($node->reveal(), $property->reveal(), $webspaceKey, $locale, null);
    }

    public function testWrite(): void
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('test', new \DateTime('2020-07-02T11:30:00'))->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn('2020-07-02T11:30:00');
        $property->getName()->willReturn('test');

        $dateTime = new DateTime();

        $dateTime->write($node->reveal(), $property->reveal(), 1, $webspaceKey, $locale, null);
    }

    public function testWriteNull(): void
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        $nodeItem = $this->prophesize(ItemInterface::class);
        $nodeItem->remove()->shouldBeCalled($nodeItem->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(true);
        $node->getProperty('test')->willReturn($nodeItem->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(null);
        $property->getName()->willReturn('test');

        $dateTime = new DateTime();

        $dateTime->write($node->reveal(), $property->reveal(), 1, $webspaceKey, $locale, null);
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->shouldBeCalled()->willReturn('2020-07-02T11:30:00');

        $dateTime = new DateTime();
        $result = $dateTime->getContentData($property->reveal());

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2020-07-02T11:30:00', $result->format('Y-m-d\TH:i:s'));
    }

    public function testGetContentDataEmpty(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->shouldBeCalled()->willReturn('');

        $dateTime = new DateTime();

        $this->assertNull($dateTime->getContentData($property->reveal()));
    }
}
