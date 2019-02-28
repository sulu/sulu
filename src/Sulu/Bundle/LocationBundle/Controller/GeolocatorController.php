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

use Sulu\Bundle\LocationBundle\Geolocator\Exception\GeolocatorNotFoundException;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for geolocator API abstraction.
 */
class GeolocatorController
{
    /**
     * @var GeolocatorManager
     */
    private $geolocatorManager;

    /**
     * @param GeolocatorManager $geolocatorManager
     */
    public function __construct(GeolocatorManager $geolocatorManager)
    {
        $this->geolocatorManager = $geolocatorManager;
    }

    /**
     * Query the configured geolocation service.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function queryAction(Request $request)
    {
        $geolocatorName = $request->get('providerName');
        $query = $request->get('query');

        try {
            $geolocator = $this->geolocatorManager->get($geolocatorName);
        } catch (GeolocatorNotFoundException $e) {
            throw new NotFoundHttpException(
                sprintf(
                    'Wrapped "%s"',
                    $e->getMessage()
                ),
                $e
            );
        }

        $res = $geolocator->locate($query);

        return new JsonResponse(['_embedded' => ['locations' => $res->toArray()]]);
    }
}
