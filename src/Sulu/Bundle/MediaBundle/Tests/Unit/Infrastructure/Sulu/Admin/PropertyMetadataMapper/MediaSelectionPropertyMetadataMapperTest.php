<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Admin\PropertyMetadataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\MediaSelectionPropertyMetadataMapper;

class MediaSelectionPropertyMetadataMapperTest extends TestCase
{
    private MediaSelectionPropertyMetadataMapper $mediaSelectionPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->mediaSelectionPropertyMetadataMapper = new MediaSelectionPropertyMetadataMapper(
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

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'ids' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'number',
                    ],
                    'minItems' => 1,
                    'uniqueItems' => true,
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['ids'],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($minOption);
        $fieldMetadata->addOption($maxOption);

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(2);
        $fieldMetadata->addOption($maxOption);

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'maxItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithIntegerishValues(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue('2');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue('3');
        $fieldMetadata->addOption($minOption);
        $fieldMetadata->addOption($maxOption);

        $jsonSchema = $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue('invalid-value');
        $fieldMetadata->addOption($minOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(-1);
        $fieldMetadata->addOption($minOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(0);
        $fieldMetadata->addOption($minOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue('invalid-value');
        $fieldMetadata->addOption($maxOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(0);
        $fieldMetadata->addOption($maxOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(1);
        $fieldMetadata->addOption($minOption);
        $fieldMetadata->addOption($maxOption);

        $this->mediaSelectionPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }
}
