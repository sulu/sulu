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
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
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
            'content' => LinkConfigurationBuilder::create()
                ->setTitle('Content')
                ->setResourceKey('content')
                ->setListAdapter('column_list')
                ->setDisplayProperties(['title'])
                ->setOverlayTitle('Title')
                ->setEmptyText('Empty')
                ->setIcon('su-document'),
            'media' => LinkConfigurationBuilder::create()
                ->setTitle('Media')
                ->setResourceKey('media')
                ->setListAdapter('table')
                ->setDisplayProperties(['title'])
                ->setOverlayTitle('Title')
                ->setEmptyText('Empty')
                ->setIcon('su-image'),
        ];

        $this->providerProphecies['content']->getConfigurationBuilder()->willReturn($configuration['content']);
        $this->providerProphecies['media']->getConfigurationBuilder()->willReturn($configuration['media']);

        $configurations = $this->pool->getConfiguration();
        $this->assertCount(2, $configurations);
        foreach ($configurations as $configuration) {
            $this->assertInstanceOf(LinkConfiguration::class, $configuration); // @phpstan-ignore-line method.alreadyNarrowedType
        }
    }
}
