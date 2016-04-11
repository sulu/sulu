<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Serializer\Tests\Unit;

use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use Sulu\Component\Serializer\ArraySerializationVisitor;

class ArraySerializationVisitorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArraySerializationVisitor
     */
    private $arraySerializationVisitor;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $propertyNamingStrategy;

    public function setUp()
    {
        $this->propertyNamingStrategy = $this->prophesize(PropertyNamingStrategyInterface::class);
        $this->arraySerializationVisitor = new ArraySerializationVisitor($this->propertyNamingStrategy->reveal());
    }

    public function testGetResult()
    {
        $this->arraySerializationVisitor->setRoot(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $this->arraySerializationVisitor->getResult());
    }
}
