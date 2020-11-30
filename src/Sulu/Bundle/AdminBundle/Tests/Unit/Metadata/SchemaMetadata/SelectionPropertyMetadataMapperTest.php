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
        $propertyMetadata->setRequired(false);

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

    public function testMapPropertyMetadataMinOccursAndMaxOccurs(): void
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

    public function testMapPropertyMetadataRequiredAndInvalidMinOccursAndMaxOccurs(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 0],
            ['name' => 'max', 'value' => -2],
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
            'minItems' => 1,
            'maxItems' => 1,
            'uniqueItems' => true,
        ], $jsonSchema);
    }
}
