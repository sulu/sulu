<?php

namespace Sulu\Bundle\LocationBundle\Geolocator\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when the third-party geolocator returns a non
 * OK response
 */
class GeolocatorHttpException extends HttpException
{
}
