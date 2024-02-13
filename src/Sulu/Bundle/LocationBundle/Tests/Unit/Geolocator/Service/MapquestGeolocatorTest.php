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
use Sulu\Bundle\LocationBundle\Geolocator\Service\MapquestGeolocator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MapquestGeolocatorTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    public function provideLocate(): array
    {
        return [
            [
                '10, Rue Alexandre Dumas, Paris',
                1,
                [
                    'displayTitle' => '10 Rue Alexandre Dumas, Paris, 75011, FR',
                    'street' => '10 Rue Alexandre Dumas',
                    'number' => null,
                    'code' => '75011',
                    'town' => 'Paris',
                    'country' => 'FR',
                    'longitude' => '2.38986',
                    'latitude' => '48.85297',
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $expectationMap
     *
     * @dataProvider provideLocate
     */
    public function testLocate(string $query, int $expectedCount, array $expectationMap): void
    {
        $fixtureName = __DIR__ . '/mapquest-responses/' . \md5($query) . '.json';
        /** @var string $fixture */
        $fixture = \file_get_contents($fixtureName);
        $mockResponse = new MockResponse($fixture);

        $httpClient = new MockHttpClient($mockResponse);
        $geolocator = new MapquestGeolocator($httpClient, 'https://example.org', 'key');

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
