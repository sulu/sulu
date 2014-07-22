<?php

namespace Sulu\Bundle\LocationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sulu\Bundle\LocationBundle\Geolocator\Exception\GeolocatorNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class GeolocatorController extends Controller
{
    public function queryAction(Request $request)
    {
        $geolocatorName = $request->get('providerName');
        $query = $request->get('query');
        $geolocatorManager = $this->get('sulu_location.geolocator.manager');

        try {
            $geolocator = $geolocatorManager->get($geolocatorName);
        } catch (GeolocatorNotFoundException $e) {
            throw new NotFoundHttpException(sprintf(
                'Wrapped "%s"', $e->getMessage()
            ), $e);
        }

        $res = $geolocator->locate($query);

        return new JsonResponse($res->toArray());
    }
}
