<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Geolocator;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;

class GeolocatorResponseTest extends TestCase
{
    protected $geolocatorResponse;

    public function setUp(): void
    {
        $this->response = new GeolocatorResponse();
        $this->location = $this->getMockBuilder(GeolocatorLocation::class)->getMock();
    }

    public function testToArray(): void
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
