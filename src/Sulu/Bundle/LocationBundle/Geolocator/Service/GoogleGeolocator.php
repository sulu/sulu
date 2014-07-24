<?php

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Guzzle\Http\ClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorLocation;

/**
 * Geolocator which uses the google geocoding API.
 *
 * https://developers.google.com/maps/documentation/geocoding
 */
class GoogleGeolocator implements GeolocatorInterface
{
    const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function locate($query)
    {
        $request = $this->client->get(self::ENDPOINT, array(), array(
            'query' => array(
                'address' => $query,
            ),
            'debug' => true
        ));

        $this->client->send($request);
        $response = $request->getResponse();

        if ($response->getStatusCode() != 200) {
            throw new GeolocatorHttpException($response->getStatusCode(), sprintf(
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

            $map = array();
            foreach ($result['address_components'] as $component) {
                foreach ($component['types'] as $type) {
                    if (isset($map[$type])) {
                        $map[$type][] = $component;
                    } else {
                        $map[$type] = array($component);
                    }
                }
            }

            $location->setId(md5(serialize($result)));
            $location->setDisplayTitle($result['formatted_address']);

            foreach (array(
                'route' => 'setStreet',
                'street_number' => 'setNumber',
                'postal_code' => 'setCode',
                'locality' => 'setTown',
                'country' => 'setCountry'
            ) as $field => $method) 
            {
                if (isset($map[$field])) {
                    $parts = array();
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

