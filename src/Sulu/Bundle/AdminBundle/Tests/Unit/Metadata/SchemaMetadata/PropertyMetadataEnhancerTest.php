<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata as SchemaPropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataEnhancer;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataEnhancerInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class PropertyMetadataEnhancerTest extends TestCase
{
    /**
     * @var ObjectProphecy<PropertyMetadataEnhancerInterface>
     */
    private $propertyMetadataEnhancer1;

    /**
     * @var ObjectProphecy<PropertyMetadataEnhancerInterface>
     */
    private $propertyMetadataEnhancer2;

    /**
     * @var ObjectProphecy<PropertyMetadataEnhancerInterface>
     */
    private $propertyMetadataEnhancer3;

    /**
     * @var PropertyMetadataEnhancer
     */
    private $propertyMetadataEnhancer;

    protected function setUp(): void
    {
        $this->propertyMetadataEnhancer1 = $this->prophesize(PropertyMetadataEnhancerInterface::class);
        $this->propertyMetadataEnhancer2 = $this->prophesize(PropertyMetadataEnhancerInterface::class);
        $this->propertyMetadataEnhancer3 = $this->prophesize(PropertyMetadataEnhancerInterface::class);

        $this->propertyMetadataEnhancer = new PropertyMetadataEnhancer([
            $this->propertyMetadataEnhancer1->reveal(),
            $this->propertyMetadataEnhancer2->reveal(),
            $this->propertyMetadataEnhancer3->reveal(),
        ]);
    }

    public function testSupports(): void
    {
        $this->expectExceptionMessage('This method should never be called and is most likely a bug.');

        $this->propertyMetadataEnhancer->supports(new PropertyMetadata());
    }

    public function testEnhancePropertyMetadata(): void
    {
        $propertyMetadata = new SchemaPropertyMetadata('property', true);
        $itemMetadata = new PropertyMetadata();

        $this->propertyMetadataEnhancer1->supports($itemMetadata)->willReturn(true);
        $this->propertyMetadataEnhancer1->enhancePropertyMetadata($propertyMetadata, $itemMetadata)
            ->shouldBeCalled()
            ->willReturn(new SchemaPropertyMetadata('property', true));

        $this->propertyMetadataEnhancer2->supports($itemMetadata)->willReturn(false);
        $this->propertyMetadataEnhancer2->enhancePropertyMetadata($propertyMetadata, $itemMetadata)->shouldNotBeCalled();

        $expectedPropertyMetadata = new SchemaPropertyMetadata('property', true);

        $this->propertyMetadataEnhancer3->supports($itemMetadata)->willReturn(true);
        $this->propertyMetadataEnhancer3->enhancePropertyMetadata($propertyMetadata, $itemMetadata)
            ->shouldBeCalled()
            ->willReturn($expectedPropertyMetadata);

        $this->assertSame(
            $expectedPropertyMetadata,
            $this->propertyMetadataEnhancer->enhancePropertyMetadata($propertyMetadata, $itemMetadata)
        );
    }
}
