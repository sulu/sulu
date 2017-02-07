<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata\Provider;

use Metadata\MergeableClassMetadata;
use Metadata\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\ChainProvider;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\VirtualPropertyMetadata;

class ChainProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This is needed for tests to determine working virtual-property detection.
     *
     * @var mixed
     */
    protected $test;

    public function testGetMetadataForClass()
    {
        $chain = [$this->getProviderMock(), $this->getProviderMock()];
        $provider = new ChainProvider($chain);

        $result = $provider->getMetadataForClass(self::class);
        $this->assertInstanceOf(ClassMetadata::class, $result);

        $this->assertEquals(['test', 'test1'], array_keys($result->propertyMetadata));
        $this->assertInstanceOf(PropertyMetadata::class, $result->propertyMetadata['test']);
        $this->assertInstanceOf(VirtualPropertyMetadata::class, $result->propertyMetadata['test1']);
    }

    protected function getProviderMock()
    {
        $classMetadata = $this->prophesize(MergeableClassMetadata::class);
        $classMetadata->propertyMetadata = [
            'test' => $this->getPropertyMock('test'),
            'test1' => $this->getPropertyMock('test1'),
        ];

        $provider = $this->prophesize(ProviderInterface::class);
        $provider->getMetadataForClass(self::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        return $provider->reveal();
    }

    protected function getPropertyMock($name)
    {
        $property = $this->prophesize(PropertyMetadata::class);
        $property->name = $name;

        return $property->reveal();
    }
}
