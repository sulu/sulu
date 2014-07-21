<?php

namespace Sulu\Bundle\LocationBundle\Geolocator\Service;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Guzzle\Http\ClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;

class NominatimGeolocator implements GeolocatorInterface
{
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
        $request = $this->client->get('', array(), array(
            'query' => array(
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
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

        $results = $request->getResponse()->json();
        $responses = array();

        foreach ($results as $result) {
            $response = new GeolocatorResponse();

            foreach (array(
                'setStreet' => 'road',
                'setNumber' => 'house_number',
                'setCode' => 'postcode',
                'setTown' => 'city',
                'setCountry' => 'country_code'
            ) as $method => $key) 
            {
                if (isset($result['address'][$key])) {
                    $response->$method($result['address'][$key]);
                }
            }

            $response->setLongitude($result['lon']);
            $response->setLatitude($result['lat']);
            $response->setDisplayTitle($result['display_name']);

            $responses[] = $response;
        }

        return $responses;
    }
}
