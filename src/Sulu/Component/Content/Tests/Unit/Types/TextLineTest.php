<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Types\TextLine;

class TextLineTest extends TestCase
{
    /**
     * @var TextLine
     */
    private $textLine;

    public function setUp(): void
    {
        $this->textLine = new TextLine(new PropertyMetadataMinMaxValueResolver());
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyStringSchema(): array
    {
        return [
            'type' => 'string',
            'maxLength' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'string',
            'minLength' => 1,
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataPattern(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'pattern', 'value' => '^[^,]*$'],
        ]);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => 2],
            ['name' => 'max_characters', 'value' => 3],
        ]);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => 2],
        ]);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max_characters', 'value' => 2],
        ]);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => '2'],
            ['name' => 'max_characters', 'value' => '3'],
        ]);

        $jsonSchema = $this->textLine->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
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
        $this->expectExceptionMessage('Parameter "min_characters" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => 'invalid-value'],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min_characters" of property "property-name" needs to be greater than or equal "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => -1],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min_characters" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => 0],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max_characters" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max_characters', 'value' => 'invalid-value'],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max_characters" of property "property-name" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max_characters', 'value' => 0],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min_characters" of property "property-name" has value "2", parameter "max_characters" needs to be greater than or equal "2"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min_characters', 'value' => 2],
            ['name' => 'max_characters', 'value' => 1],
        ]);

        $this->textLine->mapPropertyMetadata($propertyMetadata);
    }
}
