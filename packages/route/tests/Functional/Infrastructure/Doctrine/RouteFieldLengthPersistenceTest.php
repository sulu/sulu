<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Route\Domain\Model\Route;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversNothing]
class RouteFieldLengthPersistenceTest extends KernelTestCase
{
    public function testPersistAndLoadRouteWithDefaultFieldLengths(): void
    {
        $this->assertRouteRoundTripsAtConfiguredFieldLengths('test', 'default-length-test');
    }

    public function testPersistAndLoadRouteWithLegacyFieldLengthsWhenLegacyLengthEnabled(): void
    {
        $this->assertRouteRoundTripsAtConfiguredFieldLengths('test_legacy', 'legacy-length-test');
    }

    private function assertRouteRoundTripsAtConfiguredFieldLengths(string $environment, string $resourceKey): void
    {
        self::bootKernel(['environment' => $environment]);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        $webspaceLength = $classMetadata->getFieldMapping('webspace')['length'];
        $slugLength = $classMetadata->getFieldMapping('slug')['length'];

        $webspace = \str_repeat('w', $webspaceLength);
        $slug = \str_repeat('s', $slugLength);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ro_routes WHERE resource_key = ?',
            [$resourceKey],
        );

        $route = new Route($resourceKey, 'field-length-test', 'en', $slug, $webspace);

        $entityManager->persist($route);
        $entityManager->flush();
        $routeId = $route->getId();
        $entityManager->clear();

        try {
            /** @var Route|null $reloadedRoute */
            $reloadedRoute = $entityManager->find(Route::class, $routeId);

            $this->assertNotNull($reloadedRoute, 'We expect the route to be found after reloading it from the database.');
            $this->assertSame($webspace, $reloadedRoute->getWebspace(), 'We expect the webspace to not be truncated when it matches the configured field length.');
            $this->assertSame($slug, $reloadedRoute->getSlug(), 'We expect the slug to not be truncated when it matches the configured field length.');
        } finally {
            $entityManager->getConnection()->executeStatement(
                'DELETE FROM ro_routes WHERE resource_key = ?',
                [$resourceKey],
            );
        }
    }
}
