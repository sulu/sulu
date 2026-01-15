<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectPropertyMetadataMapper;
use Sulu\Bundle\PageBundle\Content\Types\Select;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class SelectTest extends TestCase
{
    private Select $select;

    public function setUp(): void
    {
        $this->select = new Select();
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new ContentPropertyMetadata();
        $propertyMetadata->setName('property');
        $propertyMetadata->setRequired(true);

        $result = $this->select->mapPropertyMetadata($propertyMetadata);

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
        $propertyMetadata = new ContentPropertyMetadata();
        $propertyMetadata->setName('property');
        $propertyMetadata->setRequired(false);

        $result = $this->select->mapPropertyMetadata($propertyMetadata);

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
