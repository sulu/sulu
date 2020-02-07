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
use Sulu\Component\Rest\ListBuilder\Filter\TextFilterType;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class TextFilterTypeTest extends TestCase
{
    /**
     * @var TextFilterType
     */
    private $textFilterType;

    /**
     * @var ListBuilderInterface
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->textFilterType = new TextFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public function provideFilter()
    {
        return [
            ['firstName', 'Max'],
            ['lastName', 'Mustermann'],
        ];
    }

    /**
     * @dataProvider provideFilter
     */
    public function testFilter($fieldName, $value)
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);

        $this->textFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder->where($fieldDescriptor->reveal(), $value)->shouldBeCalled();
    }

    public function testFilterWithInvalidOptions()
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->textFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), []);
    }
}
