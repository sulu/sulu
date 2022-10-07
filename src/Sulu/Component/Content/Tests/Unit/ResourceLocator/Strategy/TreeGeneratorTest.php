<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\ResourceLocator\Strategy;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeGenerator;

class TreeGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new TreeGenerator();

        $this->assertEquals('/test/title', $generator->generate('title', '/test'));
        $this->assertEquals('/title', $generator->generate('title'));
    }
}
