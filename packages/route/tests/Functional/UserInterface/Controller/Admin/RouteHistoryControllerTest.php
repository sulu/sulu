<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\UserInterface\Controller\Admin;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Userinterface\Controller\Admin\RouteHistoryController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversClass(RouteHistoryController::class)]
class RouteHistoryControllerTest extends SuluTestCase
{
    use PHPMatcherAssertions;

    private KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $historyRoutePageEn1A = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::1', 'en', '/test-a', 'sulu-io');
        $entityManager->persist($historyRoutePageEn1A);
        $historyRoutePageEn1B = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::1', 'en', '/test-b', 'sulu-io');
        $entityManager->persist($historyRoutePageEn1B);
        $historyRoutePageDe1A = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::1', 'de', '/test-a', 'sulu-io');
        $entityManager->persist($historyRoutePageDe1A);
        $historyRoutePageDe1B = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::1', 'de', '/test-b', 'sulu-io');
        $entityManager->persist($historyRoutePageDe1B);

        $historyRouteOtherEn1A = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::2', 'en', '/other-a', 'sulu-io');
        $entityManager->persist($historyRouteOtherEn1A);
        $historyRouteOtherDe1A = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::2', 'de', '/other-b', 'sulu-io');
        $entityManager->persist($historyRouteOtherDe1A);

        $entityManager->flush();
        $entityManager->clear();

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        self::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testcCGetAction(): void
    {
        $this->client->request('GET', '/admin/api/route-histories?locale=en&resourceKey=pages&resourceId=1&webspace=sulu-io&history=true&flat=true');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
        {
            "_embedded": {
                "route_histories": [
                    {
                        "id": "@integer@",
                        "slug": "/test-a"
                    },
                    {
                        "id": "@integer@",
                        "slug": "/test-b"
                    }
                ]
            },
            "limit": 10,
            "page": 1,
            "pages": 1,
            "total": 2
        }
        JSON, $response->getContent() ?: '');
    }

    public function testCDelete(): void
    {
        $historyRoutePageEn1C = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::1', 'en', '/test-c', 'sulu-io');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($historyRoutePageEn1C);
        $entityManager->flush();
        $id = $historyRoutePageEn1C->getId();
        $entityManager->clear();

        $this->client->request('DELETE', '/admin/api/route-histories?locale=de_at&resourceKey=pages&resourceId=1&webspace=sulu-io&history=true&ids=' . $id);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->assertNull($entityManager->find(Route::class, $id));
    }
}
