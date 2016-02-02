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

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;

class GeolocatorResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $geolocatorResponse;

    public function setUp()
    {
        $this->response = new GeolocatorResponse();
        $this->location = $this->getMock('Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation');
    }

    public function testToArray()
    {
        $expected = [
            'foo' => 'bar',
        ];

        $this->location->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($expected));

        $this->response->addLocation($this->location);

        $this->assertEquals([$expected], $this->response->toArray());
    }
}
