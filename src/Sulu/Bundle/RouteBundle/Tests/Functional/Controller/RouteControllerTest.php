<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Controller;

use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class RouteControllerTest extends SuluTestCase
{
    const TEST_ENTITY = 'AppBundle\\Entity\\Test';
    const TEST_ID = 1;
    const TEST_LOCALE = 'de';

    protected function setUp()
    {
        $this->purgeDatabase();
    }

    public function testCGetAction()
    {
        $routes = [
            $this->createRoute('/test-1'),
            $this->createRoute('/test-2', null, self::TEST_ENTITY, 2),
        ];

        $this->createRoute('/test-1-1', $routes[0]);
        $this->createRoute('/test-2-1', $routes[1]);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            sprintf(
                '/api/routes?entityClass=%s&entityId=%s&locale=%s',
                self::TEST_ENTITY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(1, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $this->assertEquals($routes[0]->getId(), $items[0]['id']);
        $this->assertEquals($routes[0]->getPath(), $items[0]['path']);
    }

    public function testCGetActionHistory()
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            sprintf(
                '/api/routes?history=true&entityClass=%s&entityId=%s&locale=%s',
                self::TEST_ENTITY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(3, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($routes[$i]->getId(), $items[$i]['id']);
            $this->assertEquals($routes[$i]->getPath(), $items[$i]['path']);
        }
    }

    public function testDelete()
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/routes/' . $targetRoute->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            sprintf(
                '/api/routes?history=true&entityClass=%s&entityId=%s&locale=%s',
                self::TEST_ENTITY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertCount(0, $result['_embedded']['routes']);
    }

    public function testDeleteHistory()
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/routes/' . $routes[0]->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            sprintf(
                '/api/routes?history=true&entityClass=%s&entityId=%s&locale=%s',
                self::TEST_ENTITY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(2, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        for ($i = 0; $i < 2; ++$i) {
            $this->assertEquals($routes[$i + 1]->getId(), $items[$i]['id']);
            $this->assertEquals($routes[$i + 1]->getPath(), $items[$i]['path']);
        }
    }

    private function createRoute(
        $path,
        Route $target = null,
        $entityClass = self::TEST_ENTITY,
        $entityId = self::TEST_ID
    ) {
        $entityManager = $this->getEntityManager();

        $route = new Route($path, $entityId, $entityClass, self::TEST_LOCALE);
        if ($target) {
            $route->setTarget($target);
            $route->setHistory(true);
        }

        $entityManager->persist($route);
        $entityManager->flush();

        return $route;
    }
}
