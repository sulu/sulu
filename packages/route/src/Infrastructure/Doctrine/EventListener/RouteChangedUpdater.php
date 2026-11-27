<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Doctrine\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Route\Domain\Exception\RouteSlugNotUniqueException;
use Sulu\Route\Domain\Model\Route;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 *
 * This is a complex mechanism, and "Test Driven Development" is the recommended way to implement any changes here.
 * When reporting a bug in the following logic, please provide a failing test case. The easiest way in most use cases
 * is to adopt the existing `RouteChangedUpdaterTest::provideRoutes` test data provider.
 */
class RouteChangedUpdater implements ResetInterface
{
    /**
     * @var array<int, array{oldSlug: string, oldWebspace: string|null, route: Route}>
     */
    private array $routeChanges = [];

    /**
     * @var Route[]
     */
    private array $routesWithTempIds = [];

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $route = $args->getObject();
        if (!$route instanceof Route) {
            return;
        }

        $oldSlug = $route->getSlug();
        if ($args->hasChangedField('slug')) {
            $oldSlug = $args->getOldValue('slug');
            \assert(\is_string($oldSlug), 'Slug is expected to be always a string.');
        }

        $oldWebspace = $route->getWebspace();
        if ($args->hasChangedField('webspace')) {
            $oldWebspace = $args->getOldValue('webspace');
            \assert(\is_string($oldWebspace) || \is_null($oldWebspace), 'Webspace is expected to be always a string or null.');
        }

        $slugChanged = $oldSlug !== $route->getSlug();

        if (!$route->isHistory() && ($slugChanged || $oldWebspace !== $route->getWebspace())) {
            $objectManager = $args->getObjectManager();

            $this->deleteRoutes(
                $objectManager->getConnection(),
                $objectManager->getClassMetadata(Route::class)->getTableName(),
                $this->findReclaimableHistoryIds($objectManager, $route, $route->getId()),
            );

            if ($slugChanged) {
                $this->claimDescendantSlugs($objectManager, $route, $oldSlug);
            }
        }

        if (!$slugChanged) {
            return;
        }

        $this->routeChanges[$route->getId()] = [
            'oldSlug' => $oldSlug,
            'oldWebspace' => $oldWebspace,
            'route' => $route,
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $route = $args->getObject();
        if (!$route instanceof Route) {
            return;
        }

        if (!$route->isHistory()) {
            // prePersist runs on persist(), outside the flush transaction: a flush failing later keeps the deletion
            $objectManager = $args->getObjectManager();

            $this->deleteRoutes(
                $objectManager->getConnection(),
                $objectManager->getClassMetadata(Route::class)->getTableName(),
                $this->findReclaimableHistoryIds($objectManager, $route, null),
            );
        }

        if (!$route->hasTemporaryId()) {
            return;
        }

        $this->routesWithTempIds[] = $route;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (0 === \count($this->routeChanges)
            && 0 === \count($this->routesWithTempIds)
        ) {
            return;
        }

        $objectManager = $args->getObjectManager();
        $connection = $objectManager->getConnection();

        $classMetadata = $objectManager->getClassMetadata(Route::class);
        $routesTableName = $classMetadata->getTableName();

        foreach ($this->routesWithTempIds as $route) {
            $tempResourceId = $route->getResourceId();
            $newResourceId = $route->generateRealResourceId();

            $updateTempResourceIdQueryBuilder = $connection->createQueryBuilder()
                ->update($routesTableName)
                ->set('resource_id', ':newResourceId')
                ->setParameter('newResourceId', $newResourceId, ParameterType::STRING)
                ->where('resource_id = :tempResourceId')
                ->setParameter('tempResourceId', $tempResourceId, ParameterType::STRING);

            $updateTempResourceIdQueryBuilder->executeStatement();
        }

        $this->routesWithTempIds = [];

        if (0 === \count($this->routeChanges)) {
            return;
        }

        foreach ($this->routeChanges as $routeChange) {
            $route = $routeChange['route'];
            $oldSlug = $routeChange['oldSlug'];
            $oldWebspace = $routeChange['oldWebspace'];
            $newSlug = $route->getSlug();
            $locale = $route->getLocale();

            $childAndGrandChildResult = $this->findDescendants($connection, $classMetadata, $route, $oldSlug);
            $childAndGrandChildIds = [];
            $childAndGrandChildHistoryRoutes = [];
            foreach ($childAndGrandChildResult as $childAndGrandChildRow) {
                $childAndGrandChildIds[] = $childAndGrandChildRow['id'];
                $childAndGrandChildHistoryRoutes[] = new Route(
                    Route::HISTORY_RESOURCE_KEY,
                    $childAndGrandChildRow['resource_key'] . '::' . $childAndGrandChildRow['resource_id'],
                    $locale,
                    $childAndGrandChildRow['slug'],
                    $childAndGrandChildRow['webspace'],
                    null, // history never has parents as they never will be updated
                );
            }

            // a route created in this request still carries its temporary id, the history needs the real one
            $resourceId = $route->hasTemporaryId() ? $route->generateRealResourceId() : $route->getResourceId();
            $historyRoute = new Route(
                Route::HISTORY_RESOURCE_KEY,
                $route->getResourceKey() . '::' . $resourceId,
                $locale,
                $oldSlug,
                $oldWebspace,
                null, // history never has parents ad they never will be updated
            );

            $this->createHistoryRoute($objectManager, $classMetadata, $historyRoute);

            if (0 !== \count($childAndGrandChildIds)) {
                $newSlugCast = '';
                if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                    $newSlugCast = '::text'; // concat seems not directly supported by dbal and parameter $1 (newSlug) is not cast to text correctly. So manually cast it here: https://github.com/sulu/sulu/pull/7726#discussion_r1930324013
                }

                // update exactly the routes the select matched, by id
                $updateQueryBuilder = $connection->createQueryBuilder()
                    ->update($routesTableName)
                    ->set('slug', 'CONCAT(:newSlug' . $newSlugCast . ', SUBSTRING(slug, ' . (\strlen($oldSlug) + 1) . '))')
                    ->setParameter('newSlug', $newSlug, ParameterType::STRING)
                    ->where('id IN (:childAndGrandChildIds)')
                    ->setParameter('childAndGrandChildIds', $childAndGrandChildIds, ArrayParameterType::INTEGER);

                $updateQueryBuilder->executeStatement();
            }

            // create child and grand history routes
            foreach ($childAndGrandChildHistoryRoutes as $childAndGrandChildHistoryRoute) {
                $this->createHistoryRoute($objectManager, $classMetadata, $childAndGrandChildHistoryRoute);
            }
        }

        $this->routeChanges = [];
    }

    /**
     * Returns the ids of the history routes of the route's own resource which block its slug, so the resource can take
     * the slug back. A route or history route of another resource in the same webspace is a conflict, which has to
     * be found before the route is written because a unique constraint violation is no usable error message. Routes
     * without webspace (e.g. articles) are not covered by the unique constraint and may share a slug.
     *
     * @return int[]
     *
     * @throws RouteSlugNotUniqueException
     */
    private function findReclaimableHistoryIds(EntityManagerInterface $objectManager, Route $route, ?int $excludeRouteId): array
    {
        $connection = $objectManager->getConnection();
        $routesTableName = $objectManager->getClassMetadata(Route::class)->getTableName();
        $webspace = $route->getWebspace();

        $queryBuilder = $connection->createQueryBuilder()
            ->select('id', 'resource_key', 'resource_id')
            ->from($routesTableName)
            ->andWhere('locale = :locale')
            ->andWhere('slug = :slug')
            ->andWhere(null === $webspace ? 'webspace IS NULL' : 'webspace = :webspace')
            ->setParameter('locale', $route->getLocale(), ParameterType::STRING)
            ->setParameter('slug', $route->getSlug(), ParameterType::STRING);

        if (null !== $webspace) {
            $queryBuilder->setParameter('webspace', $webspace, ParameterType::STRING);
        }

        if (null !== $excludeRouteId) {
            $queryBuilder
                ->andWhere('id != :excludeRouteId')
                ->setParameter('excludeRouteId', $excludeRouteId, ParameterType::INTEGER);
        }

        /** @var array<int, array{id: int, resource_key: string, resource_id: string}> $rows */
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        $ownHistoryResourceId = $route->getResourceKey() . '::' . $route->getResourceId();
        $ownHistoryIds = [];
        foreach ($rows as $row) {
            if (Route::HISTORY_RESOURCE_KEY === $row['resource_key'] && $ownHistoryResourceId === $row['resource_id']) {
                $ownHistoryIds[] = $row['id'];

                continue;
            }

            if (null === $webspace) {
                continue;
            }

            throw new RouteSlugNotUniqueException($route->getSlug(), $route->getLocale(), $webspace);
        }

        return $ownHistoryIds;
    }

    /**
     * Same as findReclaimableHistoryIds for the descendants which are moved by prefix in postFlush, deleting the
     * reclaimable history routes right away.
     *
     * @throws RouteSlugNotUniqueException
     */
    private function claimDescendantSlugs(EntityManagerInterface $objectManager, Route $route, string $oldSlug): void
    {
        $connection = $objectManager->getConnection();
        $classMetadata = $objectManager->getClassMetadata(Route::class);
        $routesTableName = $classMetadata->getTableName();
        $locale = $route->getLocale();

        $descendants = $this->findDescendants($connection, $classMetadata, $route, $oldSlug);

        $movedIds = \array_flip(\array_column($descendants, 'id'));

        foreach (\array_chunk($descendants, 500) as $chunk) {
            $newSlugs = [];
            $descendantsByNewSlug = [];
            foreach ($chunk as $descendant) {
                $newSlug = $route->getSlug() . \substr($descendant['slug'], \strlen($oldSlug));
                $newSlugs[] = $newSlug;
                $descendantsByNewSlug[($descendant['webspace'] ?? '') . '|' . $newSlug] = $descendant;
            }

            $queryBuilder = $connection->createQueryBuilder()
                ->select('id', 'webspace', 'slug', 'resource_key', 'resource_id')
                ->from($routesTableName)
                ->andWhere('locale = :locale')
                ->andWhere('slug IN (:slugs)')
                ->setParameter('locale', $locale, ParameterType::STRING)
                ->setParameter('slugs', $newSlugs, ArrayParameterType::STRING);

            /** @var array<int, array{id: int, webspace: string|null, slug: string, resource_key: string, resource_id: string}> $rows */
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

            $ownHistoryIds = [];
            foreach ($rows as $row) {
                $descendant = $descendantsByNewSlug[($row['webspace'] ?? '') . '|' . $row['slug']] ?? null;
                if (null === $descendant || isset($movedIds[$row['id']])) {
                    continue;
                }

                if (Route::HISTORY_RESOURCE_KEY === $row['resource_key']
                    && $descendant['resource_key'] . '::' . $descendant['resource_id'] === $row['resource_id']
                ) {
                    $ownHistoryIds[] = $row['id'];

                    continue;
                }

                if (null === $row['webspace']) {
                    continue;
                }

                throw new RouteSlugNotUniqueException($row['slug'], $locale, $row['webspace']);
            }

            $this->deleteRoutes($connection, $routesTableName, $ownHistoryIds);
        }
    }

    /**
     * @param int[] $ids
     */
    private function deleteRoutes(Connection $connection, string $routesTableName, array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        $connection->createQueryBuilder()
            ->delete($routesTableName)
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeStatement();
    }

    /**
     * @param ClassMetadata<Route> $classMetadata
     *
     * @return array<int, array{id: int, webspace: string|null, slug: string, resource_key: string, resource_id: string}>
     */
    private function findDescendants(Connection $connection, ClassMetadata $classMetadata, Route $route, string $oldSlug): array
    {
        $routesTableName = $classMetadata->getTableName();
        $webspace = $route->getWebspace();

        // Same-webspace descendants are matched by slug prefix (plain page routes have no
        // parent_id). A webspace-NULL route (e.g. a page_tree_route article) is matched only
        // when its parent_id resolves into this webspace, so it follows just its real anchor.
        $webspaceCondition = \is_string($webspace)
            ? '(child.webspace = :webspace OR (child.webspace IS NULL AND parent.webspace = :webspace))'
            : 'child.webspace IS NULL';

        // select all child and grand routes of oldSlug
        $queryBuilder = $connection->createQueryBuilder()
            ->from($routesTableName, 'child')
            ->leftJoin('child', $routesTableName, 'parent', 'parent.id = child.parent_id')
            ->select('child.id AS id')
            ->addSelect('child.webspace AS webspace')
            ->addSelect('child.slug AS slug')
            ->addSelect('child.resource_key AS resource_key')
            ->addSelect('child.resource_id AS resource_id')
            ->andWhere($webspaceCondition)
            ->andWhere('child.locale = :locale')
            ->andWhere('child.slug LIKE :oldSlugSlash')
            ->andWhere('child.resource_key != :historyResourceKey') // never rewrite or re-historize history routes
            ->andWhere('child.id != :changedRouteId') // never re-process the changed route itself
            ->setParameter('oldSlugSlash', $oldSlug . '/%', ParameterType::STRING)
            ->setParameter('locale', $route->getLocale(), ParameterType::STRING)
            ->setParameter('historyResourceKey', Route::HISTORY_RESOURCE_KEY, ParameterType::STRING)
            ->setParameter('changedRouteId', $route->getId(), ParameterType::INTEGER);

        if (\is_string($webspace)) {
            $queryBuilder->setParameter('webspace', $webspace, ParameterType::STRING);
        }

        /** @var array<int, array{id: int, webspace: string|null, slug: string, resource_key: string, resource_id: string}> $descendants */
        $descendants = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $descendants;
    }

    /**
     * @param ClassMetadata<Route> $classMetadata
     */
    private function createHistoryRoute(EntityManagerInterface $objectManager, ClassMetadata $classMetadata, Route $historyRoute): void
    {
        $connection = $objectManager->getConnection();
        $routesTableName = $classMetadata->getTableName();

        $historyInsertQueryBuilder = $connection->createQueryBuilder()->insert($routesTableName)
            ->values([
                $classMetadata->getColumnName('resourceKey') => ':resourceKey',
                $classMetadata->getColumnName('resourceId') => ':resourceId',
                $classMetadata->getColumnName('locale') => ':locale',
                $classMetadata->getColumnName('slug') => ':slug',
                $classMetadata->getColumnName('webspace') => ':webspace',
            ])
            ->setParameters([
                'resourceKey' => $historyRoute->getResourceKey(),
                'resourceId' => $historyRoute->getResourceId(),
                'locale' => $historyRoute->getLocale(),
                'slug' => $historyRoute->getSlug(),
                'webspace' => $historyRoute->getWebspace(),
            ]);

        if (!$classMetadata->idGenerator->isPostInsertGenerator()) {
            $historyInsertQueryBuilder->setValue(
                $classMetadata->getColumnName('id'),
                $classMetadata->idGenerator->generateId($objectManager, $historyRoute), // @phpstan-ignore-line argument.type // not really sure to return a integer id as a string so currently keep the id as int
            );
        }

        $historyInsertQueryBuilder->executeStatement();
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->routeChanges = [];
        $this->routesWithTempIds = [];
    }
}
