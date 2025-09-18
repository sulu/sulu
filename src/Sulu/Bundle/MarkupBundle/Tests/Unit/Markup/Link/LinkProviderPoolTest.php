<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Markup\Link;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPool;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\ProviderNotFoundException;

class LinkProviderPoolTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<LinkProviderInterface>[]
     */
    protected $providerProphecies = [];

    /**
     * @var LinkProviderPoolInterface
     */
    protected $pool;

    public function setUp(): void
    {
        $this->providerProphecies = [
            'content' => $this->prophesize(LinkProviderInterface::class),
            'media' => $this->prophesize(LinkProviderInterface::class),
            'page' => $this->prophesize(LinkProviderInterface::class),
        ];

        $this->pool = new LinkProviderPool(
            \array_map(
                function($provider) {
                    return $provider->reveal();
                },
                $this->providerProphecies
            )
        );
    }

    public function testGetProvider(): void
    {
        $this->assertEquals($this->providerProphecies['content']->reveal(), $this->pool->getProvider('content'));
    }

    public function testGetProviderNotFound(): void
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->pool->getProvider('test');
    }

    public function testHasProvider(): void
    {
        $this->assertTrue($this->pool->hasProvider('content'));
    }

    public function testHasProviderNotFound(): void
    {
        $this->assertFalse($this->pool->hasProvider('test'));
    }

    public function testGetConfiguration(): void
    {
        $configuration = [
            'content' => new LinkConfiguration(
                'Content',
                'content',
                'column_list',
                ['title'],
                'Title',
                'Empty',
                'su-document'
            ),
            'media' => new LinkConfiguration(
                'Media',
                'media',
                'table',
                ['title'],
                'Title',
                'Empty',
                'su-document'
            ),
        ];

        $this->providerProphecies['content']->getConfiguration()->willReturn($configuration['content']);
        $this->providerProphecies['media']->getConfiguration()->willReturn($configuration['media']);
        $this->providerProphecies['page']->getConfiguration()->willReturn(null);

        $this->assertEquals($configuration, $this->pool->getConfiguration());
    }
}
