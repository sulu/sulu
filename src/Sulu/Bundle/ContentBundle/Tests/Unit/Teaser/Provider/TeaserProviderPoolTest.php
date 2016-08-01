<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Teaser\Provider;

use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\ProviderNotFoundException;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPool;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;

class TeaserProviderPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TeaserProviderInterface[]
     */
    private $providers;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    public function setUp()
    {
        $this->providers = [
            'content' => $this->prophesize(TeaserProviderInterface::class),
            'media' => $this->prophesize(TeaserProviderInterface::class),
        ];

        $this->teaserProviderPool = new TeaserProviderPool(
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
        $this->assertEquals($this->providers['content']->reveal(), $this->teaserProviderPool->getProvider('content'));
    }

    public function testGetProviderNotFound()
    {
        $this->setExpectedException(ProviderNotFoundException::class);

        $this->teaserProviderPool->getProvider('test');
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->teaserProviderPool->hasProvider('content'));
    }

    public function testHasProviderNotFound()
    {
        $this->assertFalse($this->teaserProviderPool->hasProvider('test'));
    }

    public function testGetConfiguration()
    {
        $configuration = [
            'content' => new TeaserConfiguration('sulu_test.content', 'content@sulutest'),
            'media' => new TeaserConfiguration('sulu_test.media', 'media@sulutest'),
        ];

        $this->providers['content']->getConfiguration()->willReturn($configuration['content']);
        $this->providers['media']->getConfiguration()->willReturn($configuration['media']);

        $this->assertEquals($configuration, $this->teaserProviderPool->getConfiguration());
    }
}
