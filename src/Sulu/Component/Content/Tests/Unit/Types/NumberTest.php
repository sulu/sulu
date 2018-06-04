<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use Prophecy\Argument;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as NodePropertyInterface;
use PHPCR\PropertyType;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Types\Number;

class NumberTest extends TestCase
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var Number
     */
    private $number;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var NodePropertyInterface
     */
    private $nodeProperty;

    public function setUp()
    {
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->nodeProperty = $this->prophesize(NodePropertyInterface::class);

        $this->number = new Number($this->template);
    }

    public function testRead()
    {
        $content = 12.3;

        $this->node->hasProperty('i18n:de-test')->willReturn(true)->shouldBeCalled();
        $this->property->getName()->willReturn('i18n:de-test');
        $this->node->getPropertyValue('i18n:de-test', PropertyType::DOUBLE)->willReturn($content);

        $this->property->setValue($content)->shouldBeCalled();

        $this->number->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadWithoutExistingProperty()
    {
        $this->property->getName()->willReturn('i18n:de-test');
        $this->node->hasProperty('i18n:de-test')->willReturn(false)->shouldBeCalled();
        $this->node->getPropertyValue(Argument::any())->shouldNotBeCalled();

        $this->property->setValue(null)->shouldBeCalled();

        $this->number->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite()
    {
        $content = 15;

        $this->property->getName()->willReturn('i18n:de-test');
        $this->property->getValue()->willReturn(15);

        $this->node->setProperty('i18n:de-test', $content, PropertyType::DOUBLE)->shouldBeCalled();
        $this->number->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteNoValue()
    {
        $this->property->getName()->willReturn('i18n:de-test');
        $this->property->getValue()->willReturn(null);
        $this->nodeProperty->remove()->shouldBeCalled();

        $this->node->hasProperty('i18n:de-test')->willReturn(true)->shouldBeCalled();
        $this->node->getProperty('i18n:de-test')->willReturn($this->nodeProperty->reveal())->shouldBeCalled();
        $this->number->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }
}
