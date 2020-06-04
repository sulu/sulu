<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RouteControllerTest extends SuluTestCase
{
    const TEST_ENTITY = 'AppBundle\\Entity\\Test';

    const TEST_RESOURCE_KEY = 'tests';

    const TEST_ID = 1;

    const TEST_LOCALE = 'de';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testGenerate()
    {
        $this->client->request(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => self::TEST_LOCALE,
                'resourceKey' => self::TEST_RESOURCE_KEY,
                'parts' => [
                    'title' => 'test',
                    'year' => '2019',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($result['resourcelocator'], '/prefix/2019/test');
    }

    public function testCGetAction()
    {
        $routes = [
            $this->createRoute('/test-1'),
            $this->createRoute('/test-2', null, self::TEST_ENTITY, 2),
        ];

        $this->createRoute('/test-1-1', $routes[0]);
        $this->createRoute('/test-2-1', $routes[1]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request(
            'GET',
            \sprintf(
                '/api/routes?resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(1, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $this->assertEquals($routes[0]->getId(), $items[0]['id']);
        $this->assertEquals($routes[0]->getPath(), $items[0]['path']);
    }

    public function testCGetActionNotExistingResourceKey()
    {
        $this->client->request(
            'GET',
            \sprintf(
                '/api/routes?resourceKey=%s&id=%s&locale=%s',
                'articles',
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCGetActionHistory()
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(3, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $items = [
            $items[0]['id'] => $items[0],
            $items[1]['id'] => $items[1],
            $items[2]['id'] => $items[2],
        ];

        for ($i = 0; $i < 3; ++$i) {
            $id = $routes[$i]->getId();

            $this->assertEquals($routes[$i]->getId(), $items[$id]['id']);
            $this->assertEquals($routes[$i]->getPath(), $items[$id]['path']);
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

        $this->entityManager->flush();
        $this->entityManager->clear();

        $targetRouteId = $targetRoute->getId();

        $this->client->request('DELETE', '/api/routes?ids=' . $targetRouteId);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
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

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('DELETE', '/api/routes?ids=' . $routes[0]->getId());
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(2, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $items = [
            $items[0]['id'] => $items[0],
            $items[1]['id'] => $items[1],
        ];

        for ($i = 0; $i < 2; ++$i) {
            $id = $routes[$i + 1]->getId();

            $this->assertEquals($routes[$i + 1]->getId(), $items[$id]['id']);
            $this->assertEquals($routes[$i + 1]->getPath(), $items[$id]['path']);
        }
    }

    private function createRoute(
        $path,
        Route $target = null,
        $entityClass = self::TEST_ENTITY,
        $entityId = self::TEST_ID
    ) {
        $route = new Route($path, $entityId, $entityClass, self::TEST_LOCALE);
        if ($target) {
            $route->setTarget($target);
            $route->setHistory(true);
        }

        $this->entityManager->persist($route);

        return $route;
    }
}
