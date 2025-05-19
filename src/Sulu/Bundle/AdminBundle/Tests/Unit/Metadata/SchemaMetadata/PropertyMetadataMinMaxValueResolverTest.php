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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;

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
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($maxOption);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testResolveMinMaxValueMinOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueMaxOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(2);
        $fieldMetadata->addOption($maxOption);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertNull($minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(2, $minMaxValue->max);
    }

    public function testResolveMinMaxValueWithoutParams(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertNull($minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueWithoutParamsRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(1, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertNull($minMaxValue->max);
    }

    public function testResolveMinMaxValueWithIntegerishValues(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($maxOption);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $this->assertTrue(\property_exists($minMaxValue, 'min'));
        $this->assertSame(2, $minMaxValue->min);
        $this->assertTrue(\property_exists($minMaxValue, 'max'));
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testResolveMinMaxValueWithDifferentParamNames(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $minOption = new OptionMetadata();
        $minOption->setName('minItems');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);
        $maxOption = new OptionMetadata();
        $maxOption->setName('maxItems');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($maxOption);

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
            $fieldMetadata,
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

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue('invalid-value');
        $fieldMetadata->addOption($minOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }

    public function testResolveMinMaxValueMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(-1);
        $fieldMetadata->addOption($minOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }

    public function testResolveMinMaxValueMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(0);
        $fieldMetadata->addOption($minOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }

    public function testResolveMinMaxValueMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue('invalid-value');
        $fieldMetadata->addOption($maxOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }

    public function testResolveMinMaxValueMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(0);
        $fieldMetadata->addOption($maxOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }

    public function testResolveMinMaxValueMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(1);
        $fieldMetadata->addOption($maxOption);

        $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);
    }
}
