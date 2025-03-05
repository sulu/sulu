<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Doctrine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Infrastructure\Doctrine\EventListener\RouteChangedUpdater;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @phpstan-type RouteData array{resourceId: string, locale?: string, slug: string, site?: string|null, parentSlug?: string|null}
 */
#[CoversClass(RouteChangedUpdater::class)]
class RouteChangedUpdaterTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes, false);
    }

    protected function tearDown(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        parent::tearDown();
    }

    /**
     * @param RouteData[] $routes
     * @param RouteData[] $expectedRoutes
     */
    #[DataProvider('provideRoutes')]
    public function testUpdateRoute(
        array $routes,
        string $changeRoute,
        array $expectedRoutes,
    ): void {
        /** @var RouteRepositoryInterface $repository */
        $repository = self::getContainer()->get(RouteRepositoryInterface::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $firstRoute = null;
        $createdRoutes = [];
        $count = 0;
        foreach ($routes as $routeData) {
            $route = $this->createRoute($routeData);
            $uniqueKey = ($route->getSite() ?? '') . $route->getLocale() . $route->getSlug();
            $parentUniqueKey = ($route->getSite() ?? '') . $route->getLocale() . ($routeData['parentSlug'] ?? '');
            $parentRoute = $createdRoutes[$parentUniqueKey] ?? null;

            $route->setParentRoute($parentRoute);
            $repository->add($route);
            $createdRoutes[$uniqueKey] = $route;

            $firstRoute ??= $route;

            ++$count;

            if (0 === $count % 1000) {
                $entityManager->flush();
                \gc_collect_cycles();
            }
        }

        $entityManager->flush();
        $entityManager->clear();
        $this->assertNotNull($firstRoute);
        $firstRoute = $entityManager->getReference(Route::class, $firstRoute->getId());
        $this->assertNotNull($firstRoute);
        $firstRoute->setSlug($changeRoute);
        $entityManager->flush();
        $entityManager->clear();

        foreach ($expectedRoutes as $expectedRoute) {
            $route = $repository->findOneBy([
                'resourceKey' => 'page',
                'resourceId' => $expectedRoute['resourceId'],
                'locale' => $expectedRoute['locale'] ?? 'en',
                'site' => $expectedRoute['site'] ?? null,
            ]);

            $this->assertNotNull($route, \sprintf(
                'Expected route with resourceId "%s", locale "%s" and site "%s" not found.',
                $expectedRoute['resourceId'],
                $expectedRoute['locale'] ?? 'en',
                $expectedRoute['site'] ?? 'NULL',
            ));

            $this->assertSame($expectedRoute['slug'], $route->getSlug());
            $this->assertSame($expectedRoute['parentSlug'] ?? null, $route->getParentRoute()?->getSlug());

            ++$count;
            if (0 === $count % 100) {
                $entityManager->clear();
            }
            if (0 === $count % 1000) {
                \gc_collect_cycles();
            }
        }

        $expectedHistoryRoutes = [];
        foreach ($expectedRoutes as $expectedRoute) {
            foreach ($routes as $route) {
                if ($expectedRoute['resourceId'] === $route['resourceId']
                    && $expectedRoute['slug'] !== $route['slug']
                    && ($expectedRoute['locale'] ?? 'en') !== ($route['locale'] ?? 'en')
                    && ($expectedRoute['site'] ?? null) !== ($route['site'] ?? null)
                ) {
                    $expectedHistoryRoutes[] = $route;
                }
            }
        }

        if (\count($expectedHistoryRoutes)) {
            foreach ($expectedHistoryRoutes as $expectedHistoryRoute) {
                $route = $repository->findOneBy([
                    'locale' => $expectedHistoryRoute['locale'] ?? 'en',
                    'site' => $expectedHistoryRoute['site'] ?? null,
                    'slug' => $expectedHistoryRoute['slug'],
                ]);

                $additionalErrorInfoMessage = \sprintf(
                    'Expected route "%s" be a route history.',
                    $expectedHistoryRoute['slug'],
                );
                $this->assertNotNull($route, $additionalErrorInfoMessage);

                $this->assertSame(Route::HISTORY_RESOURCE_KEY, $route->getResourceKey(), $additionalErrorInfoMessage);
                $this->assertSame('page::' . $expectedHistoryRoute['resourceId'], $route->getResourceId(), $additionalErrorInfoMessage);
            }
        }
    }

    /**
     * @return iterable<string, array{
     *     routes: RouteData[],
     *     changeRoute: string,
     *     expectedRoutes: RouteData[],
     * }>
     */
    public static function provideRoutes(): iterable
    {
        yield 'single_route_update' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                ],
            ],
        ];

        yield 'direct_childs_update' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'parentSlug' => '/test',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-article/child-a',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/child-b',
                    'parentSlug' => '/test-article',
                ],
            ],
        ];

        yield 'nested_childs_update' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'parentSlug' => '/test/child-b',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-article/child-a',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/child-b',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test-article/child-b/grand-child-a',
                    'parentSlug' => '/test-article/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test-article/child-b/grand-child-b',
                    'parentSlug' => '/test-article/child-b',
                ],
            ],
        ];

        yield 'nested_childs_update_multiple_locales' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'locale' => 'de',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'locale' => 'de',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'locale' => 'de',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'locale' => 'de',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'locale' => 'de',
                    'parentSlug' => '/test/child-b',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-article/child-a',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/child-b',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test-article/child-b/grand-child-a',
                    'parentSlug' => '/test-article/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test-article/child-b/grand-child-b',
                    'parentSlug' => '/test-article/child-b',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'locale' => 'de',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'locale' => 'de',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'locale' => 'de',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'locale' => 'de',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'locale' => 'de',
                    'parentSlug' => '/test/child-b',
                ],
            ],
        ];

        yield 'nested_childs_update_multiple_sites' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'site' => 'website',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'site' => 'website',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'site' => 'website',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'site' => 'website',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'intranet',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'site' => 'intranet',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'site' => 'intranet',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'site' => 'intranet',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'site' => 'intranet',
                    'parentSlug' => '/test/child-b',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-article/child-a',
                    'site' => 'website',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/child-b',
                    'site' => 'website',
                    'parentSlug' => '/test-article',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test-article/child-b/grand-child-a',
                    'site' => 'website',
                    'parentSlug' => '/test-article/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test-article/child-b/grand-child-b',
                    'site' => 'website',
                    'parentSlug' => '/test-article/child-b',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'intranet',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'site' => 'intranet',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'site' => 'intranet',
                    'parentSlug' => '/test',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/child-b/grand-child-a',
                    'site' => 'intranet',
                    'parentSlug' => '/test/child-b',
                ],
                [
                    'resourceId' => '5',
                    'slug' => '/test/child-b/grand-child-b',
                    'site' => 'intranet',
                    'parentSlug' => '/test/child-b',
                ],
            ],
        ];

        // yield 'heavy_load' => static::generateNestedRoutes('/rezepte', '/rezepte-neu', 10, 100_000);
    }

    /**
     * @return array{
     *     routes: RouteData[],
     *     changeRoute: string,
     *     expectedRoutes: RouteData[],
     * }
     */
    private static function generateNestedRoutes(string $baseSlug, string $newSlug, int $depth = 10, int $totalUrls = 100000): array // @phpstan-ignore-line method.unused
    {
        $routes = [];
        $expectedRoutes = [];
        $queue = [
            [
                'resourceId' => 1,
                'slug' => $baseSlug,
                'parentSlug' => null,
                'depth' => 1,
                'uniqueSuffix' => null, // No suffix for the baseSlug
            ],
        ];
        $resourceId = 2;

        // Add independent routes to mix with the main route tree
        $independentRoots = ['/independent-route-1', '/independent-route-2', '/independent-route-3'];
        foreach ($independentRoots as $index => $independentSlug) {
            $queue[] = [
                'resourceId' => $resourceId++,
                'slug' => $independentSlug,
                'parentSlug' => null,
                'depth' => 1,
                'uniqueSuffix' => $index + 1, // Ensuring unique suffix for independent roots
            ];
        }

        while (\count($routes) < $totalUrls && $queue) {
            $node = \array_shift($queue);
            // Only apply unique suffix if it is not the baseSlug
            $uniqueSlug = null === $node['uniqueSuffix'] ? $node['slug'] : $node['slug'] . '-' . $node['uniqueSuffix'];
            $routes[] = [
                'resourceId' => (string) $node['resourceId'],
                'slug' => $uniqueSlug,
                'parentSlug' => $node['parentSlug'],
            ];

            // Modify slug to expected new route
            $expectedSlug = \str_replace($baseSlug, $newSlug, $uniqueSlug);
            $expectedRoutes[] = [
                'resourceId' => (string) $node['resourceId'],
                'slug' => $expectedSlug,
                'parentSlug' => $node['parentSlug'] ? \str_replace($baseSlug, $newSlug, $node['parentSlug']) : null,
            ];

            if ($node['depth'] < $depth) {
                for ($i = 1; $i <= 5; ++$i) { // Adjust branching factor to reach ~100k URLs
                    if (\count($routes) >= $totalUrls) {
                        break 2;
                    }
                    $childSlug = $node['slug'] . '/child-' . $i;
                    $queue[] = [
                        'resourceId' => (string) ($resourceId++),
                        'slug' => $childSlug,
                        'parentSlug' => $uniqueSlug,
                        'depth' => $node['depth'] + 1,
                        'uniqueSuffix' => $resourceId, // Use resourceId for unique suffix
                    ];
                }
            }
        }

        return [
            'routes' => $routes,
            'changeRoute' => $newSlug,
            'expectedRoutes' => $expectedRoutes,
        ];
    }

    /**
     * @param RouteData $route
     */
    private function createRoute(array $route): Route
    {
        return new Route(
            'page',
            $route['resourceId'],
            $route['locale'] ?? 'en',
            $route['slug'],
            $route['site'] ?? null,
        );
    }
}
