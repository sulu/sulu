<?php

declare(strict_types=1);

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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectPropertyMetadataMapper;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class SelectPropertyMetadataMapperTest extends TestCase
{
    private $selectPropertyMetadataMapper;

    public function setUp(): void
    {
        $this->selectPropertyMetadataMapper = new SelectPropertyMetadataMapper();
    }

    public function testMapPropertyMetadata()
    {
        $propertyMetadata = $this->createStub(ContentPropertyMetadata::class);
        $propertyMetadata->method('getName')->willReturn('property');
        $propertyMetadata->method('isRequired')->willReturn(true);

        $result = $this->selectPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata);

        $this->assertInstanceOf(PropertyMetadata::class, $result);
        $this->assertEquals('property', $result->getName());
        $this->assertTrue($result->isMandatory());

        $jsonSchema = $result->toJsonSchema();

        $this->assertIsArray($jsonSchema);
        $this->assertArrayHasKey('anyOf', $jsonSchema);
        $this->assertCount(2, $jsonSchema['anyOf']);
        $this->assertEquals(['type' => 'string'], $jsonSchema['anyOf'][0]);
        $this->assertEquals(['type' => 'number'], $jsonSchema['anyOf'][1]);
    }

    public function testMapPropertyMetadataOptional()
    {
        $propertyMetadata = $this->createStub(ContentPropertyMetadata::class);
        $propertyMetadata->method('getName')->willReturn('property');
        $propertyMetadata->method('isRequired')->willReturn(false);

        $result = $this->selectPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata);

        $this->assertInstanceOf(PropertyMetadata::class, $result);
        $this->assertEquals('property', $result->getName());
        $this->assertFalse($result->isMandatory());

        $jsonSchema = $result->toJsonSchema();

        $this->assertIsArray($jsonSchema);
        $this->assertArrayHasKey('anyOf', $jsonSchema);
        $this->assertCount(2, $jsonSchema['anyOf']);
        $this->assertEquals(['type' => 'null'], $jsonSchema['anyOf'][0]);

        $nestedSchema = $jsonSchema['anyOf'][1];
        $this->assertArrayHasKey('anyOf', $nestedSchema);
        $this->assertCount(2, $nestedSchema['anyOf']);
        $this->assertEquals(['type' => 'string'], $nestedSchema['anyOf'][0]);
        $this->assertEquals(['type' => 'number'], $nestedSchema['anyOf'][1]);
    }
}
