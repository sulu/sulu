<?php

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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class PropertyMetadataMinMaxValueResolverTest extends TestCase
{
    /**
     * @var PropertyMetadataMinMaxValueResolver
     */
    private $propertyMetadataMinMaxValueResolver;

    protected function setUp(): void
    {
        $this->propertyMetadataMinMaxValueResolver = new PropertyMetadataMinMaxValueResolver();
    }

    public function testResolveMinMaxValue(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 3],
        ]);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testResolveMinMaxValueMinOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
        ]);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueMaxOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 2],
        ]);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertNull($minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(2, $minMaxValue->max);
    }

    public function testResolveMinMaxValueWithoutParams(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertNull($minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueWithoutParamsRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(1, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueWithIntegerishValues(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => '2'],
            ['name' => 'max', 'value' => '3'],
        ]);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testResolveMinMaxValueWithDifferentParamNames(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'minItems', 'value' => 2],
            ['name' => 'maxItems', 'value' => 3],
        ]);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
            $propertyMetadata,
            'minItems',
            'maxItems'
        );

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testResolveMinMaxValueMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 'invalid-value'],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }

    public function testResolveMinMaxValueMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => -1],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }

    public function testResolveMinMaxValueMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 0],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }

    public function testResolveMinMaxValueMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 'invalid-value'],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }

    public function testResolveMinMaxValueMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 0],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }

    public function testResolveMinMaxValueMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 1],
        ]);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
    }
}
