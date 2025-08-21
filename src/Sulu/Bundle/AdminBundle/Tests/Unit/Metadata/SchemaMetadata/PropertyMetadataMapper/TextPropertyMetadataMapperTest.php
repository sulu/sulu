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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\TextPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;

class TextPropertyMetadataMapperTest extends TestCase
{
    /**
     * @var TextPropertyMetadataMapper
     */
    private $textPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->textPropertyMetadataMapper = new TextPropertyMetadataMapper(
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
     * @return array{type: 'string', maxLength: 0}
     */
    private function getEmptyStringSchema(): array
    {
        return [
            'type' => 'string',
            'maxLength' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'string',
            'minLength' => 1,
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataPattern(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('pattern');
        $option->setValue('^[^,]*$');
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'pattern' => '^[^,]*$',
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue(2);
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue(3);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 3,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue(2);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'minLength' => 2,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue(2);
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'maxLength' => 2,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithIntegerishValues(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue('2');
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue('3');
        $fieldMetadata->addOption($option);

        $jsonSchema = $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'minLength' => 2,
                    'maxLength' => 3,
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min_length" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue('invalid-value');
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min_length" of property "property-name" needs to be greater than or equal "0"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue(-1);
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min_length" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue(0);
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max_length" of property "property-name" needs to be either null or of type int');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue('invalid-value');
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max_length" of property "property-name" needs to be greater than or equal "1"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue(0);
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min_length" of property "property-name" has value "2", parameter "max_length" needs to be greater than or equal "2"');

        $fieldMetadata = new FieldMetadata('property-name');
        $option = new OptionMetadata();
        $option->setName('min_length');
        $option->setValue(2);
        $fieldMetadata->addOption($option);
        $option = new OptionMetadata();
        $option->setName('max_length');
        $option->setValue(1);
        $fieldMetadata->addOption($option);

        $this->textPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata);
    }
}
