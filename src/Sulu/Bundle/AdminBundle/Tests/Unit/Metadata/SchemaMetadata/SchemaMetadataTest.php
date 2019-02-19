<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class SchemaMetadataTest extends TestCase
{
    /**
     * It is absolutely necessary that no empty array is returned, because an empty array would be serialized as array
     * instead of an object in JSON, which would cause the JsonSchema library in the frontend to crash.
     */
    public function testEmptyJsonSchemaReturningNonEmptyArray()
    {
        $schema = new SchemaMetadata();

        $this->assertEquals(['required' => []], $schema->toJsonSchema());
    }

    public function testNestedJsonSchema()
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('title', true),
            ],
            [],
            [
                new SchemaMetadata(
                    [],
                    [
                        new SchemaMetadata(
                            [
                                new PropertyMetadata('nodeType', false, 2),
                            ]
                        ),
                        new SchemaMetadata(
                            [
                                new PropertyMetadata('nodeType', false, 4),
                            ]
                        ),
                    ]
                ),
                new SchemaMetadata(
                    [
                        new PropertyMetadata('article', true),
                    ]
                ),
            ]
        );

        $this->assertEquals(
            [
                'required' => ['title'],
                'allOf' => [
                    [
                        'required' => [],
                        'anyOf' => [
                            [
                                'required' => [],
                                'properties' => [
                                    'nodeType' => [
                                        'name' => 'nodeType',
                                        'const' => 2,
                                    ],
                                ],
                            ],
                            [
                                'required' => [],
                                'properties' => [
                                    'nodeType' => [
                                        'name' => 'nodeType',
                                        'const' => 4,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'required' => ['article'],
                    ],
                ],
            ],
            $schema->toJsonSchema()
        );
    }
}
