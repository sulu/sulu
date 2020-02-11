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
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeTransliteratorGeneratorDecorator;

class TreeTransliteratorGeneratorDecoratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new TreeGenerator();

        $transliterator = \Transliterator::create('Russian-Latin/BGN');
        $decorated = new TreeTransliteratorGeneratorDecorator($generator, $transliterator);

        $this->assertEquals('/kakvo-stava', $decorated->generate('какво-става'));
        $this->assertEquals('/kakvo-stava/maniatsi', $decorated->generate('маниаци', '/kakvo-stava'));
    }
}
