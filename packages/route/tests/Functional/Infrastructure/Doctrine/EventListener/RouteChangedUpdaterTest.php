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
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Infrastructure\Doctrine\EventListener\RouteChangedUpdater;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @phpstan-type RouteData array{resourceId: string, locale?: string, slug: string, site?: string|null, parentSlug?: string|null, parentSite?: string|null}
 */
#[CoversClass(RouteChangedUpdater::class)]
class RouteChangedUpdaterTest extends KernelTestCase
{
    use SetGetPrivatePropertyTrait;

    public static function setUpBeforeClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes, false);

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        self::ensureKernelShutdown();
    }

    public function testPersistTempRoute(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $counter = 0;
        $route = Route::createRouteWithTempId(
            'article',
            function() use (&$counter) {
                return (string) ++$counter;
            },
            'en',
            '/some-slug',
        );

        $route2 = Route::createRouteWithTempId(
            'article',
            function() use (&$counter) {
                return (string) ++$counter;
            },
            'en',
            '/some-slug',
        );

        $entityManager->persist($route);
        $entityManager->persist($route2);

        $this->assertSame(0, $counter, 'The resourceId generator should not be called before flush.');

        $entityManager->flush();
        $this->assertSame(2, $counter, 'The resourceId generator should be called inside the flush.'); // @phpstan-ignore-line method.impossibleType

        $entityManager->refresh($route);
        $this->assertSame('1', $route->getResourceId());
        $entityManager->refresh($route2);
        $this->assertSame('2', $route2->getResourceId());
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
        ?int $expectedChangedRoutes,
    ): void {
        /** @var RouteRepositoryInterface $repository */
        $repository = self::getContainer()->get(RouteRepositoryInterface::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $firstRoute = null;
        $createdRoutes = [];
        $count = 0;
        $postParentRouteSetter = [];
        foreach ($routes as $routeData) {
            $route = $this->createRoute($routeData);
            $uniqueKey = ($route->getSite() ?? '') . $route->getLocale() . $route->getSlug();
            $parentRoute = null;
            if (isset($routeData['parentSlug'])) {
                $parentUniqueKey = ($routeData['parentSite'] ?? $route->getSite() ?? '') . $route->getLocale() . $routeData['parentSlug'];
                $parentRoute = $createdRoutes[$parentUniqueKey] ?? null;
                if (null === $parentRoute) { // if parent route was not yet created we set it later see $postParentRouteSetter foreach below
                    $postParentRouteSetter[$parentUniqueKey][] = $route;
                }
            }

            foreach ($postParentRouteSetter[$uniqueKey] ?? [] as $childRoute) {
                static::setPrivateProperty($childRoute, 'parentRoute', $route);
                unset($postParentRouteSetter[$uniqueKey]);
            }

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

        $this->assertCount(0, $postParentRouteSetter, 'All post parent route setters should have been called, this is a error in the lines above or the fixtures not in tested class itself.');

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
                    && ($expectedRoute['locale'] ?? 'en') === ($route['locale'] ?? 'en')
                    && ($expectedRoute['site'] ?? null) === ($route['site'] ?? null)
                ) {
                    $expectedHistoryRoutes[] = $route;
                }
            }
        }

        if (null !== $expectedChangedRoutes) {
            $this->assertCount($expectedChangedRoutes, $expectedHistoryRoutes);
        }
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

        /** @var RouteChangedUpdater $routeChangedUpdater */
        $routeChangedUpdater = self::getContainer()->get('sulu_route.doctrine_route_changed_updater');

        $routeChanges = self::getPrivateProperty($routeChangedUpdater, 'routeChanges');
        $this->assertIsArray($routeChanges);
        $routesWithTempIds = self::getPrivateProperty($routeChangedUpdater, 'routesWithTempIds');
        $this->assertIsArray($routesWithTempIds);
        $this->assertCount(0, $routeChanges, 'There should be no route changes left after the update.');
        $this->assertCount(0, $routesWithTempIds, 'There should be no routes with temp ids left after the update.');
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
            'expectedChangedRoutes' => 1,
        ];

        yield 'not_effect_same_level_with_same_starting' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'locale' => 'en',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-2',
                    'locale' => 'en',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-2/child-a',
                    'locale' => 'en',
                    'site' => 'website',
                ],
            ],
            'changeRoute' => '/test-article',
            'expectedRoutes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test-article',
                    'locale' => 'en',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-2',
                    'locale' => 'en',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-2/child-a',
                    'locale' => 'en',
                    'site' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 1,
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
            'expectedChangedRoutes' => 3,
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
            'expectedChangedRoutes' => 5,
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
            'expectedChangedRoutes' => 5,
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
            'expectedChangedRoutes' => 5,
        ];

        yield 'cross_site_parents' => [
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
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/something-else/child-a',
                    'site' => null,
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test/something-else/child-b',
                    'site' => null,
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '10',
                    'slug' => '/test-2',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '11',
                    'slug' => '/test-2/something-else/child-a',
                    'site' => null,
                    'parentSlug' => '/test-2',
                    'parentSite' => 'website',
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
                    'slug' => '/test-article/something-else/child-a',
                    'site' => null,
                    'parentSlug' => '/test-article',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '4',
                    'slug' => '/test-article/something-else/child-b',
                    'site' => null,
                    'parentSlug' => '/test-article',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '10',
                    'slug' => '/test-2',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '11',
                    'slug' => '/test-2/something-else/child-a',
                    'site' => null,
                    'parentSlug' => '/test-2',
                    'parentSite' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 4,
        ];

        yield 'tree_full_edit_last_part_changes_keep_parent' => [
            'routes' => [
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
            ],
            'changeRoute' => '/test/child-a-edit',
            'expectedRoutes' => [
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a-edit',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 1,
        ];

        yield 'tree_full_edit_change_whole_url_remove_parent' => [
            'routes' => [
                [
                    'resourceId' => '2',
                    'slug' => '/test/child-a',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
            ],
            'changeRoute' => '/test-child-a',
            'expectedRoutes' => [
                [
                    'resourceId' => '2',
                    'slug' => '/test-child-a',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 1,
        ];

        yield 'tree_full_edit_still_connected_unrelated_child_not_updated' => [
            'routes' => [
                [
                    'resourceId' => '1',
                    'slug' => '/test',
                    'site' => 'website',
                ],
                [
                    'resourceId' => '2',
                    'slug' => '/test-child-a',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/child-b',
                    'site' => 'website',
                    'parentSlug' => '/test',
                    'parentSite' => 'website',
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
                    'slug' => '/test-child-a',
                    'site' => 'website',
                    'parentSlug' => '/test-article',
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/child-b',
                    'site' => 'website',
                    'parentSlug' => '/test-article',
                    'parentSite' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 2,
        ];

        yield 'tree_full_edit_disconnected_unrelated_child_not_updated' => [
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
                    'parentSlug' => null,
                    'parentSite' => null,
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
                    'slug' => '/test/child-a',
                    'site' => 'website',
                    'parentSlug' => null,
                    'parentSite' => null,
                ],
            ],
            'expectedChangedRoutes' => 1,
        ];

        yield 'tree_full_edit_update_child_not_directly_connected' => [
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
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test/grand-child-1', // this was before "/test/child-a/grand-child-1"
                    'site' => 'website',
                    'parentSlug' => '/test/child-a', // and so still child of "/test-child-a"
                    'parentSite' => 'website',
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
                    'parentSite' => 'website',
                ],
                [
                    'resourceId' => '3',
                    'slug' => '/test-article/grand-child-1',
                    'site' => 'website',
                    'parentSlug' => '/test-article/child-a',
                    'parentSite' => 'website',
                ],
            ],
            'expectedChangedRoutes' => 3,
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
            'expectedChangedRoutes' => null,
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
