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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Route\Domain\Model\Route;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[CoversNothing]
class RouteFieldLengthPersistenceTest extends KernelTestCase
{
    public function testPersistAndLoadRouteWithDefaultFieldLengths(): void
    {
        $this->assertRouteRoundTripsAtConfiguredFieldLengths('test');
    }

    public function testPersistAndLoadRouteWithLegacyFieldLengthsWhenLegacyLengthEnabled(): void
    {
        $this->assertRouteRoundTripsAtConfiguredFieldLengths('test_legacy');
    }

    public function testPersistRouteExceedingConfiguredFieldLengthsThrowsForDefaultFieldLengths(): void
    {
        $this->assertPersistingRouteExceedingConfiguredFieldLengthsThrows('test');
    }

    public function testPersistRouteExceedingConfiguredFieldLengthsThrowsWhenLegacyLengthEnabled(): void
    {
        $this->assertPersistingRouteExceedingConfiguredFieldLengthsThrows('test_legacy');
    }

    private function assertRouteRoundTripsAtConfiguredFieldLengths(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        // update db schema for active environment
        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ' . $entityManager->getConnection()->quoteIdentifier($classMetadata->getTableName())
        );
        (new SchemaTool($entityManager))->updateSchema([$classMetadata], true);

        $webspaceLength = $classMetadata->getFieldMapping('webspace')['length'];
        $localeLength = $classMetadata->getFieldMapping('locale')['length'];
        $slugLength = $classMetadata->getFieldMapping('slug')['length'];

        Assert::integer($webspaceLength);
        Assert::integer($localeLength);
        Assert::integer($slugLength);

        $webspace = \str_repeat('w', $webspaceLength);
        $locale = \str_repeat('l', $localeLength);
        // Fill with a unique uuid so parallel/repeated test runs never collide on the
        // (webspace, locale, slug) unique constraint and the row does not need cleanup.
        $slug = $this->fillToLength(Uuid::v4()->toRfc4122(), $slugLength);

        $route = new Route('field-length-test', 'field-length-test', $locale, $slug, $webspace);

        $entityManager->persist($route);
        $entityManager->flush();
        $routeId = $route->getId();
        $entityManager->clear();

        /** @var Route|null $reloadedRoute */
        $reloadedRoute = $entityManager->find(Route::class, $routeId);

        $this->assertNotNull($reloadedRoute, 'We expect the route to be found after reloading it from the database.');
        $this->assertSame($webspace, $reloadedRoute->getWebspace(), 'We expect the webspace to not be truncated when it matches the configured field length.');
        $this->assertSame($locale, $reloadedRoute->getLocale(), 'We expect the locale to not be truncated when it matches the configured field length.');
        $this->assertSame($slug, $reloadedRoute->getSlug(), 'We expect the slug to not be truncated when it matches the configured field length.');
    }

    private function assertPersistingRouteExceedingConfiguredFieldLengthsThrows(string $environment): void
    {
        self::bootKernel(['environment' => $environment]);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        // The `test` and `test_legacy` environments share one physical database, so the
        // `ro_routes` table must be cleared and re-synced to whichever field lengths are
        // active for the booted environment before asserting against them: clearing first
        // avoids failing the narrowing ALTER on rows a wider-length run left behind.
        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ' . $entityManager->getConnection()->quoteIdentifier($classMetadata->getTableName())
        );
        (new SchemaTool($entityManager))->updateSchema([$classMetadata], true);

        $webspaceLength = $classMetadata->getFieldMapping('webspace')['length'];
        $localeLength = $classMetadata->getFieldMapping('locale')['length'];
        $slugLength = $classMetadata->getFieldMapping('slug')['length'];

        Assert::integer($webspaceLength);
        Assert::integer($localeLength);
        Assert::integer($slugLength);

        $webspace = \str_repeat('w', $webspaceLength + 1);
        $locale = \str_repeat('l', $localeLength + 1);
        $slug = $this->fillToLength(Uuid::v4()->toRfc4122(), $slugLength + 1);

        $route = new Route('field-length-test', 'field-length-test', $locale, $slug, $webspace);

        $this->expectException(DBALException::class);

        try {
            $entityManager->persist($route);
            $entityManager->flush();
        } finally {
            $entityManager->clear();
        }
    }

    private function fillToLength(string $seed, int $length): string
    {
        return \substr(\str_pad($seed, $length, $seed), 0, $length);
    }
}
