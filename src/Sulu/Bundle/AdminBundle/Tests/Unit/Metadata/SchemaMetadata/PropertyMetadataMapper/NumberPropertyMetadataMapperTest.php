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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\NumberPropertyMetadataMapper;

class NumberPropertyMetadataMapperTest extends TestCase
{
    use ProphecyTrait;

    private NumberPropertyMetadataMapper $numberPropertyMetadataMapper;

    public function setUp(): void
    {
        $this->numberPropertyMetadataMapper = new NumberPropertyMetadataMapper();
    }

    /**
     * @return array<string, mixed>
     */
    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'number',
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfFloat(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $multipleOfOption = new OptionMetadata();
        $multipleOfOption->setName('multiple_of');
        $multipleOfOption->setValue('0.5'); // float values are by the XmlParserTrait always strings
        $fieldMetadata->addOption($multipleOfOption);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'multipleOf' => 0.5,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfIntegerish(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $multipleOfOption = new OptionMetadata();
        $multipleOfOption->setName('multiple_of');
        $multipleOfOption->setValue('2');
        $fieldMetadata->addOption($multipleOfOption);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'integer',
                    'multipleOf' => 2.0,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfInteger(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $multipleOfOption = new OptionMetadata();
        $multipleOfOption->setName('multiple_of');
        $multipleOfOption->setValue(2);
        $fieldMetadata->addOption($multipleOfOption);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'integer',
                    'multipleOf' => 2.0,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMultipleOfTooSmall(): void
    {
        $this->expectExceptionMessage('Parameter "multiple_of" of property "property-name" needs to be greater than "0"');

        $fieldMetadata = new FieldMetadata('property-name');
        $multipleOfOption = new OptionMetadata();
        $multipleOfOption->setName('multiple_of');
        $multipleOfOption->setValue(0);
        $fieldMetadata->addOption($multipleOfOption);

        $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMultipleOfInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "multiple_of" of property "property-name" needs to be either null or numeric');

        $fieldMetadata = new FieldMetadata('property-name');
        $multipleOfOption = new OptionMetadata();
        $multipleOfOption->setName('multiple_of');
        $multipleOfOption->setValue('invalid-value');
        $fieldMetadata->addOption($multipleOfOption);

        $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
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

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                    'maximum' => 3,
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

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($maxOption);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'maximum' => 3,
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

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 2,
                    'maximum' => 3,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithFloatValues(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue('1.2'); // float values are by the XmlParserTrait always strings
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue('3.4'); // float values are by the XmlParserTrait always strings
        $fieldMetadata->addOption($minOption);
        $fieldMetadata->addOption($maxOption);

        $jsonSchema = $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'number',
                    'minimum' => 1.2,
                    'maximum' => 3.4,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or numeric');

        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue('invalid-value');
        $fieldMetadata->addOption($minOption);

        $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or numeric');

        $fieldMetadata = new FieldMetadata('property-name');
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue('invalid-value');
        $fieldMetadata->addOption($maxOption);

        $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
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

        $this->numberPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }
}
