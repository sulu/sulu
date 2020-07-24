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
use Sulu\Bundle\PageBundle\Content\Types\DateTime;
use Sulu\Component\Content\Compat\PropertyInterface;

class DateTimeTest extends TestCase
{
    public function testRead()
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

    public function testReadPropertyNotExists()
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

    public function testWrite()
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

    public function testWriteNull()
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
}
