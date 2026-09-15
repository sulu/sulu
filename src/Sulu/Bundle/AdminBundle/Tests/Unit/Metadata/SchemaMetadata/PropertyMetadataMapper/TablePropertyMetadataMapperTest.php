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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\TablePropertyMetadataMapper;

class TablePropertyMetadataMapperTest extends TestCase
{
    private TablePropertyMetadataMapper $tablePropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->tablePropertyMetadataMapper = new TablePropertyMetadataMapper();
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('specs');

        $jsonSchema = $this->tablePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertArrayHasKey('anyOf', $jsonSchema);
        $this->assertSame(['type' => 'null'], $jsonSchema['anyOf'][0]);
        $this->assertSame('object', $jsonSchema['anyOf'][1]['type']);
        $this->assertArrayHasKey('head', $jsonSchema['anyOf'][1]['properties']);
        $this->assertArrayHasKey('body', $jsonSchema['anyOf'][1]['properties']);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $fieldMetadata = new FieldMetadata('specs');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->tablePropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertSame('object', $jsonSchema['type']);
        $this->assertArrayHasKey('head', $jsonSchema['properties']);
        $this->assertArrayHasKey('body', $jsonSchema['properties']);
        $this->assertSame('array', $jsonSchema['properties']['head']['type']);
        $this->assertSame('array', $jsonSchema['properties']['body']['type']);
    }
}
