<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Markup\Link;

use Sulu\Bundle\ContentBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderPool;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\ContentBundle\Markup\Link\ProviderNotFoundException;

class LinkProviderPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LinkProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var LinkProviderPoolInterface
     */
    protected $pool;

    public function setUp()
    {
        $this->providers = [
            'content' => $this->prophesize(LinkProviderInterface::class),
            'media' => $this->prophesize(LinkProviderInterface::class),
        ];

        $this->pool = new LinkProviderPool(
            array_map(
                function ($provider) {
                    return $provider->reveal();
                },
                $this->providers
            )
        );
    }

    public function testGetProvider()
    {
        $this->assertEquals($this->providers['content']->reveal(), $this->pool->getProvider('content'));
    }

    public function testGetProviderNotFound()
    {
        $this->setExpectedException(ProviderNotFoundException::class);

        $this->pool->getProvider('test');
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->pool->hasProvider('content'));
    }

    public function testHasProviderNotFound()
    {
        $this->assertFalse($this->pool->hasProvider('test'));
    }

    public function testGetConfiguration()
    {
        $configuration = [
            'content' => new LinkConfiguration('sulu_test.content', 'content@sulutest'),
            'media' => new LinkConfiguration('sulu_test.media', 'media@sulutest'),
        ];

        $this->providers['content']->getConfiguration()->willReturn($configuration['content']);
        $this->providers['media']->getConfiguration()->willReturn($configuration['media']);

        $this->assertEquals($configuration, $this->pool->getConfiguration());
    }
}
