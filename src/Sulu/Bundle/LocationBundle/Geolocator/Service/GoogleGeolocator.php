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
 * Geolocator which uses the google geocoding API.
 *
 * https://developers.google.com/maps/documentation/geocoding
 */
class GoogleGeolocator implements GeolocatorInterface
{
    const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    protected $client;
    protected $apiKey;

    /**
     * @param Client $client Guzzle HTTP client
     * @param string $apiKey API key (can be empty string)
     */
    public function __construct(ClientInterface $client, $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($query)
    {
        $endpoint = self::ENDPOINT;

        if ($this->apiKey) {
            $endpoint .= '?key=' . $this->apiKey;
        }

        $request = $this->client->get($endpoint, [], [
            'query' => [
                'address' => $query,
            ],
        ]);

        $this->client->send($request);
        $response = $request->getResponse();

        if ($response->getStatusCode() != 200) {
            throw new HttpException($response->getStatusCode(), sprintf(
                'Server at "%s" returned HTTP "%s". Body: ', $client->getUrl(), $response->getStatusCode()
            ));
        }

        $googleResponse = $request->getResponse()->json();
        $response = new GeolocatorResponse();

        if ($googleResponse['status'] != 'OK') {
            return $response;
        }

        $results = $googleResponse['results'];

        foreach ($results as $result) {
            $location = new GeolocatorLocation();

            $map = [];
            foreach ($result['address_components'] as $component) {
                foreach ($component['types'] as $type) {
                    if (isset($map[$type])) {
                        $map[$type][] = $component;
                    } else {
                        $map[$type] = [$component];
                    }
                }
            }

            // google provides no ID - so we just make one up ...
            $location->setId(md5(serialize($result)));
            $location->setDisplayTitle($result['formatted_address']);

            foreach ([
                'route' => 'setStreet',
                'street_number' => 'setNumber',
                'postal_code' => 'setCode',
                'locality' => 'setTown',
                'country' => 'setCountry',
            ] as $field => $method) {
                if (isset($map[$field])) {
                    $parts = [];
                    foreach ($map[$field] as $fieldValue) {
                        $parts[] = $fieldValue['long_name'];
                    }
                    $location->{$method}(implode(', ', $parts));
                }
            }

            $geometry = $result['geometry'];
            $location->setLongitude($geometry['location']['lng']);
            $location->setLatitude($geometry['location']['lat']);

            $response->addLocation($location);
        }

        return $response;
    }
}
