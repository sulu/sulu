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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Sulu\Bundle\LocationBundle\Geolocator\Service\GoogleGeolocator;

class GoogleGeolocatorTest extends \PHPUnit_Framework_TestCase
{
    public function provideLocate()
    {
        return [
            [
                '10, Rue Alexandre Dumas, Paris',
                1,
                [
                    'displayTitle' => '10 Rue Alexandre Dumas, 75011 Paris, France',
                    'street' => 'Rue Alexandre Dumas',
                    'number' => '10',
                    'code' => '75011',
                    'town' => 'Paris',
                    'country' => 'France',
                    'longitude' => '2.3897064000000001',
                    'latitude' => '48.852964900000003',
                ],
            ],
            [
                'Dornbirn',
                1,
                [
                    'displayTitle' => 'Dornbirn, Austria',
                    'street' => null,
                    'number' => null,
                    'code' => null,
                    'town' => 'Dornbirn',
                    'country' => 'Austria',
                    'longitude' => '9.7437899999999988',
                    'latitude' => '47.412399999999998',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideLocate
     */
    public function testLocate($query, $expectedCount, $expectationMap)
    {
        $fixtureName = __DIR__ . '/google-responses/' . md5($query) . '.json';
        $fixture = file_get_contents($fixtureName);
        $mockHandler = new MockHandler([new Response(200, [], $fixture)]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $geolocator = new GoogleGeolocator($client, '');

        $results = $geolocator->locate($query);
        $this->assertCount($expectedCount, $results);

        if (0 == count($results)) {
            return;
        }

        $result = current($results->toArray());

        foreach ($expectationMap as $field => $expectation) {
            $this->assertEquals($expectation, $result[$field]);
        }
    }

    public function testApiKey()
    {
        $mockHandler = new MockHandler([new Response(200, [], '{"status": "OK","results":[]}')]);
        $stack = HandlerStack::create($mockHandler);
        $stack->push(
            Middleware::mapRequest(
                function(Request $request) {
                    $this->assertContains('key=foobar', $request->getUri()->getQuery());

                    return $request;
                }
            )
        );

        $client = new Client(['handler' => $stack]);
        $geolocator = new GoogleGeolocator($client, 'foobar');
        $geolocator->locate('foobar');
    }
}
