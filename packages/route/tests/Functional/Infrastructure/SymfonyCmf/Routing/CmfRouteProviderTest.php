<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\SymfonyCmf\Routing;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Infrastructure\SymfonyCmf\Routing\CmfRouteProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(CmfRouteProvider::class)]
class CmfRouteProviderTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $expectedRoute = new Route(Route::HISTORY_RESOURCE_KEY, 'example::1', 'en', '/test-redirect', 'sulu-io');
        $entityManager->persist($expectedRoute);
        $unexpectedRoute = new Route('example', '1', 'en', '/test-example', 'sulu-io');
        $entityManager->persist($unexpectedRoute);

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

    public function testCmfRouter(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/test-redirect');

        $response = $client->getResponse();

        $this->assertSame(301, $response->getStatusCode(), 'Unexpected response: ' . ($response->getContent() ?: ''));
        $this->assertSame('http://localhost/en/test-example', $response->headers->get('Location'));
    }

    public function testCmfRouter404(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/test-example/not-exists');

        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode(), 'Unexpected response: ' . ($response->getContent() ?: ''));
    }
}
