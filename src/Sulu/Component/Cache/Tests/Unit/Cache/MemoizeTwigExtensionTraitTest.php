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

use Prophecy\Argument;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;

class MemoizeTwigExtensionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MemoizeTwigExtensionTrait
     */
    protected $trait;

    /**
     * @var \Twig_ExtensionInterface
     */
    protected $extension;

    /**
     * @var \ReflectionProperty
     */
    protected $extensionProperty;

    /**
     * @var MemoizeInterface
     */
    protected $memoizeCache;

    /**
     * @var \ReflectionProperty
     */
    protected $memoizeCacheProperty;

    /**
     * @var int
     */
    protected $lifeTime;

    /**
     * @var \ReflectionProperty
     */
    protected $lifeTimeProperty;

    protected function setUp()
    {
        $this->memoizeCache = $this->prophesize(MemoizeInterface::class);
        $this->extension = $this->prophesize(\Twig_ExtensionInterface::class);

        $this->trait = $this->getMockForTrait(MemoizeTwigExtensionTrait::class);

        $this->extensionProperty = new \ReflectionProperty(get_class($this->trait), 'extension');
        $this->extensionProperty->setAccessible(true);
        $this->extensionProperty->setValue($this->trait, $this->extension->reveal());

        $this->memoizeCacheProperty = new \ReflectionProperty(get_class($this->trait), 'memoizeCache');
        $this->memoizeCacheProperty->setAccessible(true);
        $this->memoizeCacheProperty->setValue($this->trait, $this->memoizeCache->reveal());

        $this->lifeTimeProperty = new \ReflectionProperty(get_class($this->trait), 'lifeTime');
        $this->lifeTimeProperty->setAccessible(true);
        $this->lifeTimeProperty->setValue($this->trait, $this->lifeTime);
    }

    public function testGetFunctions()
    {
        $before = [
            new \Twig_SimpleFunction(
                'sulu_content_load',
                function () {
                    return 1;
                }
            ),
            new \Twig_SimpleFunction(
                'sulu_content_load_parent',
                function () {
                    return 2;
                }
            ),
        ];

        $this->extension->getFunctions()->willReturn($before);
        $this->memoizeCache->memoize(Argument::type('callable'), $this->lifeTime)->will(
            function ($arguments) {
                return call_user_func($arguments[0]);
            }
        )->shouldBeCalledTimes(2);

        /** @var \Twig_SimpleFunction[] $result */
        $result = $this->trait->getFunctions();

        $this->assertInstanceOf(\Twig_SimpleFunction::class, $result[0]);
        $this->assertEquals('sulu_content_load', $result[0]->getName());

        $this->assertInstanceOf(\Twig_SimpleFunction::class, $result[1]);
        $this->assertEquals('sulu_content_load_parent', $result[1]->getName());

        $this->assertEquals(1, call_user_func($result[0]->getCallable()));
        $this->assertEquals(2, call_user_func($result[1]->getCallable()));
    }
}
