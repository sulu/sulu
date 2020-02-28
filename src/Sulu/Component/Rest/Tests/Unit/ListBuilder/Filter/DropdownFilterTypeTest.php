<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Filter;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Sulu\Component\Rest\ListBuilder\Filter\DropdownFilterType;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class DropdownFilterTypeTest extends TestCase
{
    /**
     * @var DropdownFilterType
     */
    private $dropdownFilterType;

    /**
     * @var ListBuilderInterface
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->dropdownFilterType = new DropdownFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public function provideFilter()
    {
        return [
            ['type', 'overview,homepage', ['overview', 'homepage']],
            ['status', 'active,inactive', ['active', 'inactive']],
        ];
    }

    /**
     * @dataProvider provideFilter
     */
    public function testFilter($fieldName, $value, $expected)
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->dropdownFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder->in($fieldDescriptor->reveal(), $expected)->shouldBeCalled();
    }

    public function testFilterWithInvalidOptions()
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->dropdownFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), []);
    }
}
