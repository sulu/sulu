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
use PHPCR\PropertyInterface as NodePropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Content\Types\SingleSelect;
use Sulu\Component\Content\Compat\PropertyInterface;

class SingleSelectTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SingleSelect
     */
    private $singleSelect;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<NodePropertyInterface>
     */
    private $nodeProperty;

    public function setUp(): void
    {
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->nodeProperty = $this->prophesize(NodePropertyInterface::class);

        $this->singleSelect = new SingleSelect();
    }

    public function testWrite(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->property->getValue()->willReturn('option1');

        $this->node->setProperty('i18n:de-single-select', 'option1')->shouldBeCalled();
        $this->singleSelect->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteZero(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->property->getValue()->willReturn(0);

        $this->node->setProperty('i18n:de-single-select', 0)->shouldBeCalled();
        $this->singleSelect->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteStringZero(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->property->getValue()->willReturn('0');

        $this->node->setProperty('i18n:de-single-select', '0')->shouldBeCalled();
        $this->singleSelect->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteNoValue(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->property->getValue()->willReturn(null);
        $this->nodeProperty->remove()->shouldBeCalled();

        $this->node->hasProperty('i18n:de-single-select')->willReturn(true)->shouldBeCalled();
        $this->node->getProperty('i18n:de-single-select')->willReturn($this->nodeProperty->reveal())->shouldBeCalled();
        $this->singleSelect->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testRead(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->node->hasProperty('i18n:de-single-select')->willReturn(true);
        $this->node->getPropertyValue('i18n:de-single-select')->willReturn('option1');

        $this->property->setValue('option1')->shouldBeCalled();

        $this->singleSelect->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadZero(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->node->hasProperty('i18n:de-single-select')->willReturn(true);
        $this->node->getPropertyValue('i18n:de-single-select')->willReturn(0);

        $this->property->setValue(0)->shouldBeCalled();

        $this->singleSelect->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadWithoutExistingProperty(): void
    {
        $this->property->getName()->willReturn('i18n:de-single-select');
        $this->node->hasProperty('i18n:de-single-select')->willReturn(false);

        $this->property->setValue(null)->shouldBeCalled();

        $this->singleSelect->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }
}

