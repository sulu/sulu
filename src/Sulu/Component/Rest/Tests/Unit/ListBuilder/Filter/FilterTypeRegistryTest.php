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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeNotFoundException;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;

class FilterTypeRegistryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetFieldType(): void
    {
        $textFilterType = $this->prophesize(FilterTypeInterface::class);
        $numberFilterType = $this->prophesize(FilterTypeInterface::class);
        $filterTypeRegistry = new FilterTypeRegistry(
            new \ArrayObject(['text' => $textFilterType->reveal(), 'number' => $numberFilterType->reveal()])
        );

        $this->assertEquals($textFilterType->reveal(), $filterTypeRegistry->getFilterType('text'));
        $this->assertEquals($numberFilterType->reveal(), $filterTypeRegistry->getFilterType('number'));
    }

    public function testGetNonExistingFieldType(): void
    {
        $this->expectException(FilterTypeNotFoundException::class);

        $filterTypeRegistry = new FilterTypeRegistry(new \ArrayObject());
        $filterTypeRegistry->getFilterType('non-existing');
    }
}
