<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit\Cache;

use Sulu\Component\Cache\MemoizeTwigExtensionTrait;

class MemoizeTwigExtensionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MemoizeTwigExtensionTrait
     */
    protected $trait;

    /**
     * @var \ReflectionMethod
     */
    protected $reflectionMethod;

    protected function setUp()
    {
        $this->trait = $this->getMockForTrait(MemoizeTwigExtensionTrait::class);

        $this->reflectionMethod = new \ReflectionMethod(get_class($this->trait), 'convertTwigFunctions');
        $this->reflectionMethod->setAccessible(true);
    }

    public function testConvert()
    {
        $before = [
            new \Twig_SimpleFunction('sulu_content_load', [new \stdClass(), 'load']),
            new \Twig_SimpleFunction(
                'sulu_content_load_parent',
                function () {
                }
            ),
        ];

        /** @var \Twig_SimpleFunction[] $result */
        $result = $this->reflectionMethod->invokeArgs($this->trait, [$before, $this]);

        $this->assertEquals('sulu_content_load', $result[0]->getName());
        $this->assertEquals([$this, 'load'], $result[0]->getCallable());

        $this->assertEquals('sulu_content_load_parent', $result[1]->getName());
        $this->assertEquals($before[1]->getCallable(), $result[1]->getCallable());
    }
}
