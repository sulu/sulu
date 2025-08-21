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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\SelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;

class SelectionPropertyMetadataMapperTest extends TestCase
{
    /**
     * @var SelectionPropertyMetadataMapper
     */
    private $selectionPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->selectionPropertyMetadataMapper = new SelectionPropertyMetadataMapper(
            new PropertyMetadataMinMaxValueResolver()
        );
    }

    /**
     * @return array{type: 'null'}
     */
    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    /**
     * @return array{
     *     type: 'array',
     *     items: array{type: array<string>},
     *     maxItems: 0,
     * }
     */
    private function getEmptyArraySchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
            ],
            'maxItems' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyArraySchema(),
                [
                    'type' => 'array',
                    'items' => [
                        'anyOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                    ],
                    'uniqueItems' => true,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'number'],
                ],
            ],
            'minItems' => 1,
            'uniqueItems' => true,
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue(2);
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue(3);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyArraySchema(),
                [
                    'type' => 'array',
                    'items' => [
                        'anyOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                    ],
                    'minItems' => 2,
                    'maxItems' => 3,
                    'uniqueItems' => true,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue(2);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyArraySchema(),
                [
                    'type' => 'array',
                    'items' => [
                        'anyOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                    ],
                    'minItems' => 2,
                    'uniqueItems' => true,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue(2);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyArraySchema(),
                [
                    'type' => 'array',
                    'items' => [
                        'anyOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                    ],
                    'maxItems' => 2,
                    'uniqueItems' => true,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithIntegerishValues(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue('2');
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue('3');
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyArraySchema(),
                [
                    'type' => 'array',
                    'items' => [
                        'anyOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                        ],
                    ],
                    'minItems' => 2,
                    'maxItems' => 3,
                    'uniqueItems' => true,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue('invalid-value');
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue(-1);
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue(0);
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue('invalid-value');
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue(0);
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min');
        $option->setValue(2);
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max');
        $option->setValue(1);
        $fieldMetadata->addOption($option);

        $this->selectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }
}
