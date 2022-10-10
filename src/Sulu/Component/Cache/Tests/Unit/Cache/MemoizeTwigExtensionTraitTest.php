<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

class MemoizeTwigExtensionTraitTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MemoizeTwigExtensionTrait
     */
    protected $trait;

    /**
     * @var ObjectProphecy<ExtensionInterface>
     */
    protected $extension;

    /**
     * @var \ReflectionProperty
     */
    protected $extensionProperty;

    /**
     * @var ObjectProphecy<MemoizeInterface>
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

    protected function setUp(): void
    {
        $this->memoizeCache = $this->prophesize(MemoizeInterface::class);
        $this->extension = $this->prophesize(ExtensionInterface::class);

        $this->trait = $this->getMockForTrait(MemoizeTwigExtensionTrait::class);

        $this->extensionProperty = new \ReflectionProperty(\get_class($this->trait), 'extension');
        $this->extensionProperty->setAccessible(true);
        $this->extensionProperty->setValue($this->trait, $this->extension->reveal());

        $this->memoizeCacheProperty = new \ReflectionProperty(\get_class($this->trait), 'memoizeCache');
        $this->memoizeCacheProperty->setAccessible(true);
        $this->memoizeCacheProperty->setValue($this->trait, $this->memoizeCache->reveal());

        $this->lifeTimeProperty = new \ReflectionProperty(\get_class($this->trait), 'lifeTime');
        $this->lifeTimeProperty->setAccessible(true);
        $this->lifeTimeProperty->setValue($this->trait, $this->lifeTime);
    }

    public function testGetFunctions(): void
    {
        $before = [
            new TwigFunction(
                'sulu_content_load',
                function() {
                    return 1;
                }
            ),
            new TwigFunction(
                'sulu_content_load_parent',
                function() {
                    return 2;
                }
            ),
        ];

        $this->extension->getFunctions()->willReturn($before);
        $this->memoizeCache->memoizeById('sulu_content_load', [], Argument::type('callable'), $this->lifeTime)
            ->will(
                function($arguments) {
                    return \call_user_func($arguments[2]);
                }
            );
        $this->memoizeCache->memoizeById('sulu_content_load_parent', [], Argument::type('callable'), $this->lifeTime)
            ->will(
                function($arguments) {
                    return \call_user_func($arguments[2]);
                }
            );

        /** @var TwigFunction[] $result */
        $result = $this->trait->getFunctions();

        $this->assertInstanceOf(TwigFunction::class, $result[0]);
        $this->assertEquals('sulu_content_load', $result[0]->getName());

        $this->assertInstanceOf(TwigFunction::class, $result[1]);
        $this->assertEquals('sulu_content_load_parent', $result[1]->getName());

        $this->assertEquals(1, \call_user_func($result[0]->getCallable()));
        $this->assertEquals(2, \call_user_func($result[1]->getCallable()));
    }
}
