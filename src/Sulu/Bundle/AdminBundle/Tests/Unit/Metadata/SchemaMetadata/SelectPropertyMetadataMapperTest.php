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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectPropertyMetadataMapper;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class SelectPropertyMetadataMapperTest extends TestCase
{
    private SelectPropertyMetadataMapper $selectPropertyMetadataMapper;

    public function setUp(): void
    {
        $this->selectPropertyMetadataMapper = new SelectPropertyMetadataMapper();
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = $this->createStub(ContentPropertyMetadata::class);
        $propertyMetadata->method('getName')->willReturn('property');
        $propertyMetadata->method('isRequired')->willReturn(true);

        $result = $this->selectPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata);

        $this->assertSame('property', $result->getName());
        $this->assertTrue($result->isMandatory());

        $jsonSchema = $result->toJsonSchema();

        $this->assertIsArray($jsonSchema);
        $this->assertArrayHasKey('anyOf', $jsonSchema);

        $anyOf = $jsonSchema['anyOf'];
        $this->assertIsArray($anyOf);
        $this->assertCount(2, $anyOf);
        $this->assertSame(['type' => 'string'], $anyOf[0]);
        $this->assertSame(['type' => 'number'], $anyOf[1]);
    }

    public function testMapPropertyMetadataOptional(): void
    {
        $propertyMetadata = $this->createStub(ContentPropertyMetadata::class);
        $propertyMetadata->method('getName')->willReturn('property');
        $propertyMetadata->method('isRequired')->willReturn(false);

        $result = $this->selectPropertyMetadataMapper->mapPropertyMetadata($propertyMetadata);

        $this->assertSame('property', $result->getName());
        $this->assertFalse($result->isMandatory());

        $jsonSchema = $result->toJsonSchema();

        $this->assertIsArray($jsonSchema);
        $this->assertArrayHasKey('anyOf', $jsonSchema);

        $anyOf = $jsonSchema['anyOf'];
        $this->assertIsArray($anyOf);
        $this->assertCount(2, $anyOf);
        $this->assertSame(['type' => 'null'], $anyOf[0]);

        $nestedSchema = $anyOf[1];
        $this->assertIsArray($nestedSchema);
        $this->assertArrayHasKey('anyOf', $nestedSchema);

        $nestedAnyOf = $nestedSchema['anyOf'];
        $this->assertIsArray($nestedAnyOf);
        $this->assertCount(2, $nestedAnyOf);
        $this->assertSame(['type' => 'string'], $nestedAnyOf[0]);
        $this->assertSame(['type' => 'number'], $nestedAnyOf[1]);
    }
}
