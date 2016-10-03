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

    /**
     * @var \ReflectionProperty
     */
    protected $reflectionProperty;

    protected function setUp()
    {
        $this->trait = $this->getMockForTrait(MemoizeTwigExtensionTrait::class);

        $this->reflectionMethod = new \ReflectionMethod(get_class($this->trait), 'convertTwigFunctions');
        $this->reflectionMethod->setAccessible(true);

        $this->reflectionProperty = new \ReflectionProperty(get_class($this->trait), 'extension');
        $this->reflectionProperty->setAccessible(true);
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

    public function testGetFunctions()
    {
        $before = [
            new \Twig_SimpleFunction('sulu_content_load', [new \stdClass(), 'load']),
            new \Twig_SimpleFunction(
                'sulu_content_load_parent',
                function () {
                }
            ),
        ];

        $extension = $this->prophesize(\Twig_Extension::class);
        $extension->getFunctions()->willReturn($before);

        $this->reflectionProperty->setValue($this->trait, $extension->reveal());

        $result = $this->trait->getFunctions();

        $this->assertEquals('sulu_content_load', $result[0]->getName());
        $this->assertEquals([$this->trait, 'load'], $result[0]->getCallable());

        $this->assertEquals('sulu_content_load_parent', $result[1]->getName());
        $this->assertEquals($before[1]->getCallable(), $result[1]->getCallable());
    }
}
