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
    use MemoizeTwigExtensionTrait;

    /**
     * @var ObjectProphecy<ExtensionInterface>
     */
    private $extensionMock;

    /**
     * @var ObjectProphecy<MemoizeInterface>
     */
    private $memoizeCacheMock;

    protected function setUp(): void
    {
        $this->memoizeCacheMock = $this->prophesize(MemoizeInterface::class);
        $this->extensionMock = $this->prophesize(ExtensionInterface::class);

        $this->extension = $this->extensionMock->reveal();
        $this->memoizeCache = $this->memoizeCacheMock->reveal();
    }

    public function testGetFunctions(): void
    {
        $before = [
            new TwigFunction('sulu_content_load', function() { return 1; }),
            new TwigFunction('sulu_content_load_parent', function() { return 2; }),
        ];

        $this->extensionMock->getFunctions()->willReturn($before)->shouldBeCalled();

        $this->memoizeCacheMock
            ->memoizeById('sulu_content_load', [], Argument::type('callable'), $this->lifeTime)
            ->will(function(array $arguments) {return $arguments[2](); })
            ->shouldBeCalled();
        $this->memoizeCacheMock
            ->memoizeById('sulu_content_load_parent', [], Argument::type('callable'), $this->lifeTime)
            ->will(function(array $arguments) {return $arguments[2](); })
            ->shouldBeCalled();

        /** @var TwigFunction[] $result */
        $result = $this->getFunctions();

        $this->assertInstanceOf(TwigFunction::class, $result[0]);
        $this->assertEquals('sulu_content_load', $result[0]->getName());
        $callable1 = $result[0]->getCallable();
        $this->assertIsCallable($callable1);
        $this->assertEquals(1, $callable1());

        $this->assertInstanceOf(TwigFunction::class, $result[1]);
        $this->assertEquals('sulu_content_load_parent', $result[1]->getName());
        $callable2 = $result[1]->getCallable();
        $this->assertIsCallable($callable2);
        $this->assertEquals(2, $callable2());
    }
}
