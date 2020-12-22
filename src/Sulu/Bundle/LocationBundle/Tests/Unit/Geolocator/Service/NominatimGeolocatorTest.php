<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Geolocator\Service;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\LocationBundle\Geolocator\Service\NominatimGeolocator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NominatimGeolocatorTest extends TestCase
{
    public function provideLocate()
    {
        return [
            [
                '10, Rue Alexandre Dumas, Paris',
                2,
                [
                    'displayTitle' => '10, Rue Alexandre Dumas, Ste-Marguerite, 11th Arrondissement, Paris, Ile-de-France, F-75011, Metropolitan France, European Union',
                    'street' => 'Rue Alexandre Dumas',
                    'number' => '10',
                    'code' => 'F-75011',
                    'town' => 'Paris',
                    'country' => 'FR',
                    'longitude' => '2.3898894',
                    'latitude' => '48.8529486',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideLocate
     */
    public function testLocate($query, $expectedCount, $expectationMap)
    {
        $fixtureName = __DIR__ . '/responses/' . \md5($query) . '.json';
        $fixture = \file_get_contents($fixtureName);
        $mockResponse = new MockResponse($fixture);

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new NominatimGeolocator($httpClient, 'https://example.org', 'key');

        $results = $geolocator->locate($query);
        $this->assertCount($expectedCount, $results);

        if (0 == \count($results)) {
            return;
        }

        $result = \current($results->toArray());

        foreach ($expectationMap as $field => $expectation) {
            $this->assertEquals($expectation, $result[$field]);
        }
    }
}
