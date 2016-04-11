<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Geolocator;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorManager;

class GeolocatorManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $container;
    protected $manager;

    public function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->geolocator = $this->getMock('Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface');

        $this->manager = new GeolocatorManager($this->container);
    }

    public function testManager()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('some_service_id')
            ->willReturn($this->geolocator);
        $this->manager->register('my_geolocator', 'some_service_id');

        $res = $this->manager->get('my_geolocator');

        $this->assertSame($this->geolocator, $res);
    }
}
