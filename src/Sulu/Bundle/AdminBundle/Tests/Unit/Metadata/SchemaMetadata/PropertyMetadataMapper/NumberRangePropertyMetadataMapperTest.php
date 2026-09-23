<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata\PropertyMetadataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\NumberPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\NumberRangePropertyMetadataMapper;

class NumberRangePropertyMetadataMapperTest extends TestCase
{
    private NumberRangePropertyMetadataMapper $numberRangePropertyMetadataMapper;

    public function setUp(): void
    {
        $this->numberRangePropertyMetadataMapper = new NumberRangePropertyMetadataMapper(new NumberPropertyMetadataMapper());
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $propertyMetadata = $this->numberRangePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);

        $this->assertSame('property-name', $propertyMetadata->getName());
        $this->assertFalse($propertyMetadata->isMandatory());
        $this->assertEquals([
            'anyOf' => [
                ['type' => 'null'],
                [
                    'type' => 'object',
                    'properties' => [
                        'from' => ['type' => 'number'],
                        'to' => ['type' => 'number'],
                    ],
                    'required' => ['from', 'to'],
                ],
            ],
        ], $propertyMetadata->toJsonSchema());
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $propertyMetadata = $this->numberRangePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);

        $this->assertTrue($propertyMetadata->isMandatory());
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'number'],
                'to' => ['type' => 'number'],
            ],
            'required' => ['from', 'to'],
        ], $propertyMetadata->toJsonSchema());
    }

    public function testMapPropertyMetadataAppliesTheNumberParamsToBothBounds(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        foreach (['min' => '-50', 'max' => '150', 'multiple_of' => '0.5'] as $name => $value) {
            $option = new OptionMetadata();
            $option->setName($name);
            $option->setValue($value);
            $fieldMetadata->addOption($option);
        }

        $jsonSchema = $this->numberRangePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $bound = ['type' => 'number', 'minimum' => -50, 'maximum' => 150, 'multipleOf' => 0.5];
        $this->assertEquals([
            'type' => 'object',
            'properties' => ['from' => $bound, 'to' => $bound],
            'required' => ['from', 'to'],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRejectsInvalidParamsLikeANumberField(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('step');
        $option->setValue('-1');
        $fieldMetadata->addOption($option);

        $this->expectException(\InvalidArgumentException::class);

        $this->numberRangePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }
}
