<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Controller;

use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GeolocatorController
{
    /**
     * @var GeolocatorInterface
     */
    private $geolocator;

    public function __construct(GeolocatorInterface $geolocator)
    {
        $this->geolocator = $geolocator;
    }

    /**
     * Query the configured geolocation service.
     */
    public function queryAction(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        $res = $this->geolocator->locate($query);

        return new JsonResponse(['_embedded' => ['locations' => $res->toArray()]]);
    }
}
