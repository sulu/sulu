<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Teaser\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\ProviderNotFoundException;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderPool;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderPoolInterface;

class TeaserProviderPoolTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TeaserProviderInterface[]
     */
    private $providers;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    public function setUp(): void
    {
        $this->providers = [
            'content' => $this->prophesize(TeaserProviderInterface::class),
            'media' => $this->prophesize(TeaserProviderInterface::class),
        ];

        $this->teaserProviderPool = new TeaserProviderPool(
            \array_map(
                function($provider) {
                    return $provider->reveal();
                },
                $this->providers
            )
        );
    }

    public function testGetProvider(): void
    {
        $this->assertEquals($this->providers['content']->reveal(), $this->teaserProviderPool->getProvider('content'));
    }

    public function testGetProviderNotFound(): void
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->teaserProviderPool->getProvider('test');
    }

    public function testHasProvider(): void
    {
        $this->assertTrue($this->teaserProviderPool->hasProvider('content'));
    }

    public function testHasProviderNotFound(): void
    {
        $this->assertFalse($this->teaserProviderPool->hasProvider('test'));
    }

    public function testGetConfiguration(): void
    {
        $configuration = [
            'content' => new TeaserConfiguration('Pages', 'pages', 'column_list', ['title'], 'Choose'),
            'media' => new TeaserConfiguration('Media', 'media', 'masonry', ['title', 'version'], 'Choose'),
        ];

        $this->providers['content']->getConfiguration()->willReturn($configuration['content']);
        $this->providers['media']->getConfiguration()->willReturn($configuration['media']);

        $this->assertEquals($configuration, $this->teaserProviderPool->getConfiguration());
    }
}
