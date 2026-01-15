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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\SelectPropertyMetadataMapper;

class SelectPropertyMetadataMapperTest extends TestCase
{
    private SelectPropertyMetadataMapper $select;

    public function setUp(): void
    {
        $this->select = new SelectPropertyMetadataMapper();
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new FieldMetadata('property');
        $propertyMetadata->setRequired(true);

        $result = $this->select->mapPropertyMetadata($propertyMetadata);

        $this->assertSame('property', $result->getName());
        $this->assertTrue($result->isMandatory());

        $this->assertSame(
            [
                'anyOf' => [
                    [
                        'type' => 'string',
                    ],
                    [
                        'type' => 'number',
                    ],
                ],
            ],
            $result->toJsonSchema(),
        );
    }

    public function testMapPropertyMetadataOptional(): void
    {
        $propertyMetadata = new FieldMetadata('property');
        $propertyMetadata->setRequired(false);

        $result = $this->select->mapPropertyMetadata($propertyMetadata);

        $this->assertSame('property', $result->getName());
        $this->assertFalse($result->isMandatory());

        $this->assertSame(
            [
                'anyOf' => [
                    [
                        'type' => 'null',
                    ],
                    [
                        'anyOf' => [
                            [
                                'type' => 'string',
                            ],
                            [
                                'type' => 'number',
                            ],
                        ],
                    ],
                ],
            ],
            $result->toJsonSchema(),
        );
    }
}
