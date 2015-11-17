<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\Types\Date;
use Sulu\Component\Content\Compat\PropertyInterface;

class DateTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';
        $dateValue = new \DateTime();

        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test')->willReturn(true);
        $node->getPropertyValue('test')->willReturn($dateValue);
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('test');
        $property->setValue($dateValue->format('Y-m-d'))->willReturn(null);

        $date = new Date('test.html.twig');

        $date->read($node->reveal(), $property->reveal(), $webspaceKey, $locale, null);

        $property->setValue($dateValue->format('Y-m-d'))->shouldBeCalled();
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
        $property->setValue('')->willReturn(null);

        $date = new Date('test.html.twig');

        $date->read($node->reveal(), $property->reveal(), $webspaceKey, $locale, null);

        $property->setValue('')->shouldBeCalled();
    }

    public function testWrite()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';
        $dateValue = new \DateTime();

        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn($dateValue->format('Y-m-d'));
        $property->getName()->willReturn('test');

        $date = new Date('test.html.twig');

        $date->write($node->reveal(), $property->reveal(), 1, $webspaceKey, $locale, null);

        $node->setProperty('test', $dateValue)->shouldBeCalled();
    }
}
