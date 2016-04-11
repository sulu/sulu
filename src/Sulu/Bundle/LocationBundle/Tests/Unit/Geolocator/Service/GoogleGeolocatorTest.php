<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Geolocator\Service;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Log\ArrayLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Plugin\Mock\MockPlugin;
use Sulu\Bundle\LocationBundle\Geolocator\Service\GoogleGeolocator;

class GoogleGeolocatorTest extends \PHPUnit_Framework_TestCase
{
    protected $geolocator;
    protected $mockPlugin;
    protected $client;

    public function setUp()
    {
        $this->client = new Client();
        $this->mockPlugin = new MockPlugin();
        $this->client->addSubscriber($this->mockPlugin);

        $this->logAdapter = new ArrayLogAdapter();
        $this->loggingPlugin = new LogPlugin($this->logAdapter);
        $this->client->addSubscriber($this->loggingPlugin);

        $this->geolocator = new GoogleGeolocator($this->client, '');
    }

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
        $this->mockPlugin->addResponse(new Response(200, null, $fixture));

        $results = $this->geolocator->locate($query);
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
        $this->mockPlugin->addResponse(new Response(200, null, '{"status": "OK","results":[]}'));
        $geolocator = new GoogleGeolocator($this->client, 'foobar');
        $geolocator->locate('foobar');
        $logs = $this->logAdapter->getLogs();
        $this->assertCount(1, $logs);
        $log = current($logs);
        $this->assertContains('key=foobar', $log['message']);
    }
}
