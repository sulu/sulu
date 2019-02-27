<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Controller;

use Sulu\Bundle\LocationBundle\Controller\GeolocatorController;
use Sulu\Bundle\LocationBundle\Geolocator\Exception\GeolocatorNotFoundException;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorInterface;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorManager;
use Sulu\Bundle\LocationBundle\Geolocator\GeolocatorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeolocatorControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testQuery()
    {
        $geolocatorResponse = $this->prophesize(GeolocatorResponse::class);
        $geolocatorResponse->toArray()->willReturn(['test']);

        $locator = $this->prophesize(GeolocatorInterface::class);
        $locator->locate('Dornbirn Teststraße 6')->willReturn($geolocatorResponse->reveal());

        $manager = $this->prophesize(GeolocatorManager::class);
        $manager->get('test-provider')->willReturn($locator->reveal());

        $controller = new GeolocatorController($manager->reveal());

        $request = $this->prophesize(Request::class);
        $request->get('providerName')->willReturn('test-provider');
        $request->get('query')->willReturn('Dornbirn Teststraße 6');

        $response = $controller->queryAction($request->reveal());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['_embedded' => ['locations' => ['test']]], json_decode($response->getContent(), true));
    }

    public function testQueryNotExistingProvider()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $manager = $this->prophesize(GeolocatorManager::class);
        $manager->get('test-provider')->willThrow(new GeolocatorNotFoundException());
        $controller = new GeolocatorController($manager->reveal());

        $request = $this->prophesize(Request::class);
        $request->get('providerName')->willReturn('test-provider');
        $request->get('query')->willReturn('Dornbirn Teststraße 6');

        $controller->queryAction($request->reveal());
    }
}
