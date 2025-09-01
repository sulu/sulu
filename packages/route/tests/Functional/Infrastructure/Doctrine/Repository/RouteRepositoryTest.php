<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Infrastructure\Doctrine\Repository\RouteRepository;

#[CoversClass(RouteRepository::class)]
class RouteRepositoryTest extends KernelTestCase
{
    private RouteRepository $routeRepository;

    public static function setUpBeforeClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $expectedRoute = new Route(Route::HISTORY_RESOURCE_KEY, 'example::1', 'en', '/test', 'the_site');
        $entityManager->persist($expectedRoute);
        $unexpectedRoute = new Route(Route::HISTORY_RESOURCE_KEY, 'example::2', 'en', '/test-2', 'the_site');
        $entityManager->persist($unexpectedRoute);
        $expectedLocalesRouteEn = new Route('example', '1', 'en', '/example', 'the_site');
        $entityManager->persist($expectedLocalesRouteEn);
        $expectedLocalesRouteDe = new Route('example', '1', 'de', '/beispiel', 'the_site');
        $entityManager->persist($expectedLocalesRouteDe);
        $expectedLocalesRouteFr = new Route('example', '1', 'fr', '/exemple', 'the_site');
        $entityManager->persist($expectedLocalesRouteFr);
        $unexpectedLocalesRouteEs = new Route('example', '1', 'es', '/ejemplo', 'the_site');
        $entityManager->persist($unexpectedLocalesRouteEs);

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
        $this->routeRepository = self::getContainer()->get('sulu_route.route_repository');
    }

    public function testFindOneBySiteSlugLocale(): void
    {
        $route = $this->routeRepository->findOneBy([
            'site' => 'the_site',
            'slug' => '/test',
            'locale' => 'en',
        ]);

        $this->assertNotNull($route);
        $this->assertSame('/test', $route->getSlug());
        $this->assertSame('en', $route->getLocale());
        $this->assertSame('the_site', $route->getSite());
    }

    public function testFindOneByResourceAndLocale(): void
    {
        $route = $this->routeRepository->findOneBy([
            'locale' => 'en',
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
            'resourceId' => 'example::1',
        ]);

        $this->assertNotNull($route);
        $this->assertSame('en', $route->getLocale());
        $this->assertSame(Route::HISTORY_RESOURCE_KEY, $route->getResourceKey());
        $this->assertSame('example::1', $route->getResourceId());
    }

    public function testFindOneByResourceAndLocales(): void
    {
        $routes = [...$this->routeRepository->findBy([
            'locales' => ['en', 'de', 'fr'],
            'resourceKey' => 'example',
            'resourceId' => '1',
        ])];

        \usort($routes, function(Route $a, Route $b) {
            return \strcmp($a->getLocale(), $b->getLocale());
        });

        $this->assertSame(
            [
                [
                    'resourceKey' => 'example',
                    'resourceId' => '1',
                    'locale' => 'de',
                    'slug' => '/beispiel',
                    'site' => 'the_site',
                ],
                [
                    'resourceKey' => 'example',
                    'resourceId' => '1',
                    'locale' => 'en',
                    'slug' => '/example',
                    'site' => 'the_site',
                ],
                [
                    'resourceKey' => 'example',
                    'resourceId' => '1',
                    'locale' => 'fr',
                    'slug' => '/exemple',
                    'site' => 'the_site',
                ],
            ],
            \array_map(function(Route $route): array {
                return [
                    'resourceKey' => $route->getResourceKey(),
                    'resourceId' => $route->getResourceId(),
                    'locale' => $route->getLocale(),
                    'slug' => $route->getSlug(),
                    'site' => $route->getSite(),
                ];
            }, $routes),
        );
    }

    public function testFindOneNotFound(): void
    {
        $route = $this->routeRepository->findOneBy([
            'locale' => 'en',
            'resourceKey' => 'the_site',
            'resourceId' => 'example::' . \PHP_INT_MAX,
        ]);

        $this->assertNull($route);
    }

    public function testExistBySiteSlugLocale(): void
    {
        $this->assertTrue($this->routeRepository->existBy([
            'site' => 'the_site',
            'slug' => '/example',
            'locale' => 'en',
        ]));
    }

    public function testExistBySiteWithExclude(): void
    {
        $this->assertFalse($this->routeRepository->existBy([
            'site' => 'the_site',
            'slug' => '/example',
            'locale' => 'en',
            'excludeResource' => [
                'resourceKey' => 'example',
                'resourceId' => '1',
            ],
        ]));
    }

    public function testExistBySiteSlugLocaleNotFound(): void
    {
        $this->assertFalse($this->routeRepository->existBy([
            'site' => 'the_site',
            'slug' => '/example-1',
            'locale' => 'en',
        ]));
    }
}
