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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectionPropertyMetadataMapper;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class SelectionPropertyMetadataMapperTest extends TestCase
{
    /**
     * @var SelectionPropertyMetadataMapper
     */
    private $selectionPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->selectionPropertyMetadataMapper = new SelectionPropertyMetadataMapper();
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'array',
            'items' => [
                'required' => [],
                'anyOf' => [
                    ['type' => 'string', 'required' => []],
                    ['type' => 'number', 'required' => []],
                ],
            ],
            'uniqueItems' => true,
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'array',
            'items' => [
                'required' => [],
                'anyOf' => [
                    ['type' => 'string', 'required' => []],
                    ['type' => 'number', 'required' => []],
                ],
            ],
            'minItems' => 1,
            'uniqueItems' => true,
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 3],
            ['name' => 'max', 'value' => 5],
        ]);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'array',
            'items' => [
                'required' => [],
                'anyOf' => [
                    ['type' => 'string', 'required' => []],
                    ['type' => 'number', 'required' => []],
                ],
            ],
            'minItems' => 3,
            'maxItems' => 5,
            'uniqueItems' => true,
        ], $jsonSchema);
    }

    public function testGetValidatedMinMaxValue(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 3],
        ]);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertSame(2, $minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueMinOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
        ]);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertSame(2, $minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertNull($minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueMaxOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 2],
        ]);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertNull($minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertSame(2, $minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueWithoutParams(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertNull($minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertNull($minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueWithoutParamsRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertSame(1, $minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertNull($minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueWithIntegerishValues(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => '2'],
            ['name' => 'max', 'value' => '3'],
        ]);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertSame(2, $minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueWithDifferentParamNames(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'minItems', 'value' => 2],
            ['name' => 'maxItems', 'value' => 3],
        ]);

        $minMaxValue = SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata, 'minItems', 'maxItems');

        $this->assertObjectHasAttribute('min', $minMaxValue);
        $this->assertSame(2, $minMaxValue->min);
        $this->assertObjectHasAttribute('max', $minMaxValue);
        $this->assertSame(3, $minMaxValue->max);
    }

    public function testGetValidatedMinMaxValueMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 'invalid-value'],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }

    public function testGetValidatedMinMaxValueMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => -1],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }

    public function testGetValidatedMinMaxValueMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 0],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }

    public function testGetValidatedMinMaxValueMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 'invalid-value'],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }

    public function testGetValidatedMinMaxValueMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 0],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }

    public function testGetValidatedMinMaxValueMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 1],
        ]);

        SelectionPropertyMetadataMapper::getValidatedMinMaxValue($propertyMetadata);
    }
}
