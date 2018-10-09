<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\ResourceMetadata\Schema;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Property;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;

class SchemaTest extends TestCase
{
    public function testEmptyJsonSchema()
    {
        $schema = new Schema();

        $this->assertEquals([], $schema->toJsonSchema());
    }

    public function testNestedJsonSchema()
    {
        $schema = new Schema(
            [
                new Property('title', true),
            ],
            [],
            [
                new Schema(
                    [],
                    [
                        new Schema(
                            [
                                new Property('nodeType', false, 2),
                            ]
                        ),
                        new Schema(
                            [
                                new Property('nodeType', false, 4),
                            ]
                        ),
                    ]
                ),
                new Schema(
                    [
                        new Property('article', true),
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
                                        'name' => 'nodeType',
                                        'const' => 2,
                                    ],
                                ],
                            ],
                            [
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
