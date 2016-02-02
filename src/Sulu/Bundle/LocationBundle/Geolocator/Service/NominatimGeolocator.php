<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Guzzle\Http\ClientInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Geolocator which uses the open street maps nominatim service.
 *
 * http://wiki.openstreetmap.org/wiki/Nominatim
 */
class NominatimGeolocator implements GeolocatorInterface
{
    protected $client;
    protected $baseUrl;

    public function __construct(ClientInterface $client, $baseUrl = '')
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($query)
    {
        $request = $this->client->get($this->baseUrl, [], [
            'query' => [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
            ],
        ]);

        $this->client->send($request);
        $response = $request->getResponse();

        if ($response->getStatusCode() != 200) {
            throw new HttpException($response->getStatusCode(), sprintf(
                'Server at "%s" returned HTTP "%s". Body: ', $client->getUrl(), $response->getStatusCode()
            ));
        }

        $results = $request->getResponse()->json();
        $response = new GeolocatorResponse();

        foreach ($results as $result) {
            $location = new GeolocatorLocation();

            foreach ([
                'setStreet' => 'road',
                'setNumber' => 'house_number',
                'setCode' => 'postcode',
                'setTown' => 'city',
                'setCountry' => 'country_code',
            ] as $method => $key) {
                if (isset($result['address'][$key])) {
                    $location->$method($result['address'][$key]);
                }
            }

            $location->setId($result['place_id']);
            $location->setLongitude($result['lon']);
            $location->setLatitude($result['lat']);
            $location->setDisplayTitle($result['display_name']);

            $response->addLocation($location);
        }

        return $response;
    }
}
