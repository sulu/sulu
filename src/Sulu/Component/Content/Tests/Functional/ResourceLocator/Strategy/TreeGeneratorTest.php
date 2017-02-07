<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\ResourceLocator\Strategy;

use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeGenerator;

class TreeGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = new TreeGenerator();

        $this->assertEquals('/test/title', $generator->generate('title', '/test'));
        $this->assertEquals('/title', $generator->generate('title'));
    }
}
