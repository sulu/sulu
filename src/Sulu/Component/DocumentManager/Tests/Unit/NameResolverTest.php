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

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\NameResolver;

class NameResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $parentNode;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var NameResolver
     */
    private $nameResolver;

    public function setUp(): void
    {
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->nameResolver = new NameResolver();
    }

    public function testResolveWithNotExistingName(): void
    {
        $this->parentNode->hasNode('foo')->willReturn(false);
        $name = $this->nameResolver->resolveName($this->parentNode->reveal(), 'foo');

        $this->assertEquals('foo', $name);
    }

    public function testResolveIncrementWithExistingName(): void
    {
        $this->parentNode->hasNode('foo')->willReturn(true);
        $this->parentNode->hasNode('foo-1')->willReturn(true);
        $this->parentNode->hasNode('foo-2')->willReturn(false);

        $name = $this->nameResolver->resolveName($this->parentNode->reveal(), 'foo');
        $this->assertEquals('foo-2', $name);
    }

    public function testResolveForNode(): void
    {
        $this->parentNode->hasNode('foo')->willReturn(true);
        $this->parentNode->getNode('foo')->willReturn($this->node->reveal());

        $name = $this->nameResolver->resolveName($this->parentNode->reveal(), 'foo', $this->node->reveal());
        $this->assertEquals('foo', $name);
    }

    public function testResolveForNodeWithIncrement(): void
    {
        $this->parentNode->hasNode('foo')->willReturn(true);
        $this->parentNode->getNode('foo')->willReturn($this->node->reveal());

        $node = $this->prophesize(NodeInterface::class);

        $this->parentNode->hasNode('foo-1')->willReturn(true);
        $this->parentNode->getNode('foo-1')->willReturn($node->reveal());

        $name = $this->nameResolver->resolveName($this->parentNode->reveal(), 'foo', $node->reveal());
        $this->assertEquals('foo-1', $name);
    }
}
