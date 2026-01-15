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
        $propertyMetadata = new ContentPropertyMetadata();
        $propertyMetadata->setName('property');
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
