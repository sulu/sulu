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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class ArrayMetadataTest extends TestCase
{
    public function provideGetter()
    {
        return [
            ['title', true],
            ['article', false],
        ];
    }

    /**
     * @dataProvider provideGetter
     */
    public function testGetter($name, $mandatory)
    {
        $property = new ArrayMetadata($name, $mandatory, new SchemaMetadata());
        $this->assertEquals($name, $property->getName());
        $this->assertEquals($mandatory, $property->isMandatory());
    }

    public function provideToJsonSchema()
    {
        return [
            [
                'title',
                new SchemaMetadata(),
                ['name' => 'title', 'type' => 'array', 'items' => ['required' => []]],
            ],
            [
                'article',
                new SchemaMetadata([new PropertyMetadata('test1', true), new PropertyMetadata('test2', false)]),
                ['name' => 'article', 'type' => 'array', 'items' => ['required' => ['test1']]],
            ],
            [
                'article',
                new SchemaMetadata([new PropertyMetadata('test1', true), new PropertyMetadata('test2', true)]),
                ['name' => 'article', 'type' => 'array', 'items' => ['required' => ['test1', 'test2']]],
            ],
        ];
    }

    /**
     * @dataProvider provideToJsonSchema
     */
    public function testToJsonSchema($name, $schemaMetadata, $expectedSchema)
    {
        $property = new ArrayMetadata($name, false, $schemaMetadata);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($jsonSchema, $expectedSchema);
    }
}
