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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

class SchemaMetadataTest extends TestCase
{
    /**
     * It is absolutely necessary that no empty array is returned, because an empty array would be serialized as array
     * instead of an object in JSON, which would cause the JsonSchema library in the frontend to crash.
     */
    public function testEmptyJsonSchemaReturningNonEmptyArray(): void
    {
        $schema = new SchemaMetadata();

        $this->assertEquals([
            'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
        ], $schema->toJsonSchema());
    }

    public function testWithDefinitions(): void
    {
        $schema = new SchemaMetadata();

        $schema->addDefinition('test1', new SchemaMetadata(
            [
                new PropertyMetadata('title2', true),
            ]
        ));

        $schema->addDefinition('test2', new SchemaMetadata(
            [
                new PropertyMetadata('title2', true),
            ]
        ));

        $this->assertEquals([
            'definitions' => [
                'test1' => [
                    'required' => ['title2'],
                    'type' => 'object',
                ],
                'test2' => [
                    'required' => ['title2'],
                    'type' => 'object',
                ],
            ],
            'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
        ], $schema->toJsonSchema());
    }

    public function testNestedJsonSchema(): void
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
                                new PropertyMetadata('nodeType', false, new ConstMetadata(2)),
                            ]
                        ),
                        new SchemaMetadata(
                            [
                                new PropertyMetadata('nodeType', false, new ConstMetadata(4)),
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
                        'anyOf' => [
                            [
                                'properties' => [
                                    'nodeType' => [
                                        'const' => 2,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                            [
                                'properties' => [
                                    'nodeType' => [
                                        'const' => 4,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                    [
                        'required' => ['article'],
                        'type' => 'object',
                    ],
                ],
                'type' => 'object',
            ],
            $schema->toJsonSchema()
        );
    }

    public function testNestedPropertyNames(): void
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('attributes/1', false, new NumberMetadata(null, 10.0)),
                new PropertyMetadata('attributes/2', true, new NumberMetadata(0.0)),
            ]
        );

        $this->assertEquals(
            [
                'properties' => [
                    'attributes' => [
                        'properties' => [
                            '1' => [
                                'maximum' => 10.0,
                                'type' => 'number',
                            ],
                            '2' => [
                                'minimum' => 0.0,
                                'type' => 'number',
                            ],
                        ],
                        'required' => ['2'],
                        'type' => 'object',
                    ],
                ],
                'type' => 'object',
            ],
            $schema->toJsonSchema()
        );
    }

    /**
     * PHP coerces numeric-string array keys to integers, so a zero-based group of segments forms a list which
     * json_encode would serialize as a JSON array. This can only be caught on the encoded JSON, because the coercion
     * already happens when the expected array literal is created.
     */
    public function testNestedNumericPropertyNamesEncodeAsObject(): void
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('attributes/0', false, new NumberMetadata(0.0)),
                new PropertyMetadata('attributes/1', false, new NumberMetadata(null, 10.0)),
            ]
        );

        $this->assertJsonStringEqualsJsonString(
            '{"type":"object","properties":{"attributes":{"type":"object","properties":{
                "0":{"minimum":0,"type":"number"},"1":{"maximum":10,"type":"number"}}}}}',
            (string) \json_encode($schema->toJsonSchema())
        );
    }

    /**
     * A field type without a registered PropertyMetadataMapper has no schema metadata, so a group whose children all
     * lack one has nothing to validate and has to be omitted like a flat property is. An empty group would encode as
     * "options":[] - a JSON array where the "properties" keyword requires a schema object.
     */
    public function testNestedPropertyNamesWithoutSchemaAreOmitted(): void
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('title', false, new StringMetadata()),
                new PropertyMetadata('options/flag', false),
                new PropertyMetadata('options/note', false),
            ]
        );

        $this->assertJsonStringEqualsJsonString(
            '{"type":"object","properties":{"title":{"type":"string"}}}',
            (string) \json_encode($schema->toJsonSchema())
        );
    }

    public function testNestedPropertyNamesKeepFieldConstraints(): void
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('settings/text', false, new StringMetadata(3, 10)),
                new PropertyMetadata('settings/number', false, new NumberMetadata(0.0, 100.0)),
                new PropertyMetadata('settings/selection', false, new ArrayMetadata(new StringMetadata(), 1, 5)),
            ]
        );

        $this->assertEquals(
            [
                'properties' => [
                    'settings' => [
                        'properties' => [
                            'text' => [
                                'type' => 'string',
                                'minLength' => 3,
                                'maxLength' => 10,
                            ],
                            'number' => [
                                'minimum' => 0.0,
                                'maximum' => 100.0,
                                'type' => 'number',
                            ],
                            'selection' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                                'minItems' => 1,
                                'maxItems' => 5,
                            ],
                        ],
                        'type' => 'object',
                    ],
                ],
                'type' => 'object',
            ],
            $schema->toJsonSchema()
        );
    }
}
