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
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

class PathBuilderTest extends TestCase
{
    private $pathBuilder;

    public function setUp(): void
    {
        $pathRegistry = new PathSegmentRegistry([
            'one' => 'one',
            'two' => 'two',
        ]);
        $this->pathBuilder = new PathBuilder($pathRegistry);
    }

    /**
     * It should build a path
     * Using a combination of tokens and literal values.
     */
    public function testBuild(): void
    {
        $result = $this->pathBuilder->build(['%one%', '%two%', 'four']);
        $this->assertEquals('/one/two/four', $result);
    }

    /**
     * It should build "/" for an empty array.
     */
    public function testBuildEmpty(): void
    {
        $this->assertEquals('/', $this->pathBuilder->build([]));
    }

    /**
     * It should build "/" for an array with "/".
     */
    public function testBuildSingleSlash(): void
    {
        $this->assertEquals('/', $this->pathBuilder->build(['/']));
    }

    /**
     * It should replace "//" with "/".
     */
    public function testBuildNoDoubleSlash(): void
    {
        $this->assertEquals('/hello/world', $this->pathBuilder->build(['hello', '', '', 'world']));
    }

    /**
     * It should allow sub paths.
     */
    public function testBuildSubPath(): void
    {
        $this->assertEquals('/hello/world/goodbye/world/k', $this->pathBuilder->build(['hello', 'world/goodbye/world', 'k']));
    }
}
