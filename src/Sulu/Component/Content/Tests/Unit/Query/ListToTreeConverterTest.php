<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Query;

use Sulu\Component\Content\Query\ListToTreeConverter;

class ListToTreeConverterTest extends \PHPUnit_Framework_TestCase
{
    private function createItem($path, $number)
    {
        return ['path' => $path, 'a' => $number];
    }

    public function testConvert()
    {
        $i = 0;
        $data = [
            $this->createItem('/', $i++),       // 0
            $this->createItem('/a/a/a', $i++), // 1
            $this->createItem('/a/a', $i++), // 2
            $this->createItem('/a', $i++), // 3
            $this->createItem('/b/a', $i++), // 4
            $this->createItem('/b', $i++), // 5
            $this->createItem('/b/b', $i++), // 6
            $this->createItem('/c', $i++), // 7
        ];

        $expected = [
            [
                'path' => '/',
                'a' => 0,
                'children' => [
                    [
                        'path' => '/a',
                        'a' => 3,
                        'children' => [
                            [
                                'path' => '/a/a',
                                'a' => 2,
                                'children' => [
                                    [
                                        'path' => '/a/a/a',
                                        'a' => 1,
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'path' => '/b',
                        'a' => 5,
                        'children' => [
                            [
                                'path' => '/b/a',
                                'a' => 4,
                                'children' => [],
                            ],
                            [
                                'path' => '/b/b',
                                'a' => 6,
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'path' => '/c',
                        'a' => 7,
                        'children' => [],
                    ],
                ],
            ],
        ];

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);
    }

    public function testConvertWithoutRoot()
    {
        $i = 0;
        $data = [
            $this->createItem('/a/a/a', $i++), // 0
            $this->createItem('/a/a', $i++), // 1
            $this->createItem('/a', $i++), // 2
            $this->createItem('/b/a', $i++), // 3
            $this->createItem('/b', $i++), // 4
            $this->createItem('/b/b', $i++), // 5
            $this->createItem('/c', $i++), // 6
        ];

        $expected = [
            [
                'path' => '/a',
                'a' => 2,
                'children' => [
                    [
                        'path' => '/a/a',
                        'a' => 1,
                        'children' => [
                            [
                                'path' => '/a/a/a',
                                'a' => 0,
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'path' => '/b',
                'a' => 4,
                'children' => [
                    [
                        'path' => '/b/a',
                        'a' => 3,
                        'children' => [],
                    ],
                    [
                        'path' => '/b/b',
                        'a' => 5,
                        'children' => [],
                    ],
                ],
            ],
            [
                'path' => '/c',
                'a' => 6,
                'children' => [],
            ],
        ];

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);
    }

    public function testConvertWithEmptyItems()
    {
        $i = 0;
        $data = [
            $this->createItem('/a/a/a', $i++), // 0
            $this->createItem('/a', $i++), // 1
            $this->createItem('/b/a', $i++), // 2
            $this->createItem('/b/b', $i++), // 3
        ];

        // /a/a missing => no /a/a/a
        // /b missing => no /b/a
        // /b missing => no /b/b

        $expected = [
            [
                'path' => '/a',
                'a' => 1,
                'children' => [],
            ],
        ];

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);
    }

    public function testConvertWithEmptyItemsWithMoveUp()
    {
        $i = 0;
        $data = [
            $this->createItem('/a/a/a', $i++), // 0
            $this->createItem('/a', $i++), // 1
            $this->createItem('/b/a', $i++), // 2
            $this->createItem('/b/b', $i++), // 3
        ];

        $expected = [
            [
                'path' => '/a',
                'a' => 1,
                'children' => [
                    [
                        'path' => '/a/a/a',
                        'a' => 0,
                        'children' => [],
                    ],
                ],
            ],
            [
                'path' => '/b/a',
                'a' => 2,
                'children' => [],
            ],
            [
                'path' => '/b/b',
                'a' => 3,
                'children' => [],
            ],
        ];

        $converter = new ListToTreeConverter(true);
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);
    }

    public function testConvertEmptyArray()
    {
        $converter = new ListToTreeConverter();
        $result = $converter->convert([]);
        $this->assertEmpty($result);
    }

    public function testConvertWithChildren()
    {
        $i = 0;
        $data = [
            $this->createItem('/', $i++),       // 0
            $this->createItem('/c/a', $i++),    // 1
            $this->createItem('/a/a/a', $i++),  // 2
            $this->createItem('/a/a', $i++),    // 3
            $this->createItem('/a', $i++),      // 4
            $this->createItem('/b/a', $i++),    // 5
            $this->createItem('/b', $i++),      // 6
            $this->createItem('/b/b', $i++),    // 7
            $this->createItem('/c', $i++),      // 8
        ];

        $expected = [
            [
                'path' => '/',
                'a' => 0,
                'children' => [
                    [
                        'path' => '/a',
                        'a' => 4,
                        'children' => [
                            [
                                'path' => '/a/a',
                                'a' => 3,
                                'children' => [
                                    [
                                        'path' => '/a/a/a',
                                        'a' => 2,
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'path' => '/b',
                        'a' => 6,
                        'children' => [
                            [
                                'path' => '/b/a',
                                'a' => 5,
                                'children' => [],
                            ],
                            [
                                'path' => '/b/b',
                                'a' => 7,
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'path' => '/c',
                        'a' => 8,
                        'children' => [
                            [
                                'path' => '/c/a',
                                'a' => 1,
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);
    }
}
