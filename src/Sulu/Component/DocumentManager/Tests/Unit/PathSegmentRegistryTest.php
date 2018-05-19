<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use Sulu\Component\DocumentManager\PathSegmentRegistry;

class PathSegmentRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathSegmentRegistry
     */
    private $pathRegistry;

    public function setUp()
    {
        $this->pathRegistry = new PathSegmentRegistry(
            [
                'base' => 'cmf',
                'foobar' => 'barfoo',
            ]
        );
    }

    /**
     * It should retrieve a path segment.
     */
    public function testGetPathSegment()
    {
        $segment = $this->pathRegistry->getPathSegment('base');
        $this->assertEquals('cmf', $segment);
    }

    /**
     * It should throw an exception when the given path segment role does not exist.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown path segment "not exist". Known path segments: "base", "foobar"
     */
    public function testThrowException()
    {
        $this->pathRegistry->getPathSegment('not exist');
    }
}
