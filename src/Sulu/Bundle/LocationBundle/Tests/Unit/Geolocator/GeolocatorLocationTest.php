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

class GeolocatorLocationTest extends TestCase
{
    /**
     * @var GeolocatorLocation
     */
    protected $location;

    public function setUp(): void
    {
        $this->location = new GeolocatorLocation();
    }

    public function testToArray(): void
    {
        $data = [
            'id' => '123-123-123',
            'displayTitle' => 'This is title',
            'street' => 'This is street',
            'number' => 'This is number',
            'code' => 'This is code',
            'town' => 'This is town',
            'country' => 'AT',
            'longitude' => 50.123,
            'latitude' => -1.123,
        ];

        foreach ($data as $propName => $value) {
            $this->location->{'set' . \ucfirst($propName)}($value);
        }

        $data['name'] = 'This is title';

        $res = $this->location->toArray();
        $this->assertEquals($data, $res);
    }
}
