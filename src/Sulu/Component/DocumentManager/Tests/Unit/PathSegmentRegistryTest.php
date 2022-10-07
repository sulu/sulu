<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

class PathSegmentRegistryTest extends TestCase
{
    /**
     * @var PathSegmentRegistry
     */
    private $pathRegistry;

    public function setUp(): void
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
    public function testGetPathSegment(): void
    {
        $segment = $this->pathRegistry->getPathSegment('base');
        $this->assertEquals('cmf', $segment);
    }

    /**
     * It should throw an exception when the given path segment role does not exist.
     */
    public function testThrowException(): void
    {
        $this->expectExceptionMessage('Unknown path segment "not exist". Known path segments: "base", "foobar"');
        $this->expectException(\InvalidArgumentException::class);
        $this->pathRegistry->getPathSegment('not exist');
    }
}
