<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Routing\Defaults;

use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProvider;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;

class DefaultsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteDefaultsProviderInterface
     */
    private $defaultsProvider;

    private $providers = [];

    public function setUp()
    {
        $this->providers = [
            $this->prophesize(RouteDefaultsProviderInterface::class),
            $this->prophesize(RouteDefaultsProviderInterface::class),
            $this->prophesize(RouteDefaultsProviderInterface::class),
        ];

        $this->defaultsProvider = new RouteDefaultsProvider(
            array_map(
                function ($provider) {
                    return $provider->reveal();
                },
                $this->providers
            )
        );
    }

    public function testSupportFalse()
    {
        foreach ($this->providers as $provider) {
            $provider->supports('Test')->shouldBeCalled()->willReturn(false);
        }

        $this->assertFalse($this->defaultsProvider->supports('Test'));
    }

    public function testSupport()
    {
        $this->providers[0]->supports('Test')->shouldBeCalled()->willReturn(true);
        $this->providers[1]->supports('Test')->shouldNotBeCalled()->willReturn(false);
        $this->providers[2]->supports('Test')->shouldNotBeCalled()->willReturn(false);

        $this->assertTrue($this->defaultsProvider->supports('Test'));
    }

    public function testGetByEntityFalse()
    {
        foreach ($this->providers as $provider) {
            $provider->supports('Test')->shouldBeCalled()->willReturn(false);
            $provider->getByEntity('test', '1', null)->shouldNotBeCalled();
        }

        $this->assertNull($this->defaultsProvider->getByEntity('Test', '1'));
    }

    public function testGetByEntity()
    {
        $this->providers[0]->supports('Test')->shouldBeCalled()->willReturn(true);
        $this->providers[0]->getByEntity('Test', '1', null)->shouldBeCalled()->willReturn(['test' => 1]);
        $this->providers[1]->supports('Test')->shouldNotBeCalled()->willReturn(false);
        $this->providers[1]->getByEntity('test', '1', null)->shouldNotBeCalled();
        $this->providers[2]->supports('Test')->shouldNotBeCalled()->willReturn(false);
        $this->providers[2]->getByEntity('test', '1', null)->shouldNotBeCalled();

        $this->assertEquals(['test' => 1], $this->defaultsProvider->getByEntity('Test', '1'));
    }
}
