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

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
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

        if ($oldSlug === $route->getSlug()) {
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
            $webspace = $route->getWebspace();

            // match descendants by slug prefix, not the parent_id FK (unset for "route" fields);
            // OR-NULL keeps cross-site children attached
            $webspaceCondition = \is_string($webspace)
                ? '(webspace = :webspace OR webspace IS NULL)'
                : 'webspace IS NULL';

            // select all child and grand routes of oldSlug
            $selectQueryBuilder = $connection->createQueryBuilder()
                ->from($routesTableName)
                ->select('webspace')
                ->addSelect('slug')
                ->addSelect('resource_key')
                ->addSelect('resource_id')
                ->andWhere($webspaceCondition)
                ->andWhere('locale = :locale')
                ->andWhere('slug LIKE :oldSlugSlash')
                ->andWhere('resource_key != :historyResourceKey') // never rewrite or re-historize history routes
                ->andWhere('id != :changedRouteId') // never re-process the changed route itself
                ->setParameter('oldSlugSlash', $oldSlug . '/%', ParameterType::STRING)
                ->setParameter('locale', $locale, ParameterType::STRING)
                ->setParameter('historyResourceKey', Route::HISTORY_RESOURCE_KEY, ParameterType::STRING)
                ->setParameter('changedRouteId', $route->getId(), ParameterType::INTEGER);

            if (\is_string($webspace)) {
                $selectQueryBuilder->setParameter('webspace', $webspace, ParameterType::STRING);
            }

            /**
             * @var array<int, array{
             *     webspace: string|null,
             *     slug: string,
             *     resource_key: string,
             *     resource_id: string,
             * }> $childAndGrandChildResult
             */
            $childAndGrandChildResult = $selectQueryBuilder->executeQuery()->iterateAssociative();
            $childAndGrandChildHistoryRoutes = [];
            foreach ($childAndGrandChildResult as $childAndGrandChildRow) {
                $childAndGrandChildHistoryRoutes[] = new Route(
                    Route::HISTORY_RESOURCE_KEY,
                    $childAndGrandChildRow['resource_key'] . '::' . $childAndGrandChildRow['resource_id'],
                    $locale,
                    $childAndGrandChildRow['slug'],
                    $childAndGrandChildRow['webspace'],
                    null, // history never has parents as they never will be updated
                );
            }

            $historyRoute = new Route(
                Route::HISTORY_RESOURCE_KEY,
                $route->getResourceKey() . '::' . $route->getResourceId(),
                $locale,
                $oldSlug,
                $oldWebspace,
                null, // history never has parents ad they never will be updated
            );

            $this->createHistoryRoute($objectManager, $classMetadata, $historyRoute);

            if (0 !== \count($childAndGrandChildHistoryRoutes)) {
                $newSlugCast = '';
                if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                    $newSlugCast = '::text'; // concat seems not directly supported by dbal and parameter $1 (newSlug) is not cast to text correctly. So manually cast it here: https://github.com/sulu/sulu/pull/7726#discussion_r1930324013
                }

                // update child and grand routes
                $updateQueryBuilder = $connection->createQueryBuilder()
                    ->update($routesTableName)
                    ->set('slug', 'CONCAT(:newSlug' . $newSlugCast . ', SUBSTRING(slug, ' . (\strlen($oldSlug) + 1) . '))')
                    ->setParameter('newSlug', $newSlug, ParameterType::STRING)
                    ->andWhere($webspaceCondition)
                    ->andWhere('locale = :locale')
                    ->andWhere('slug LIKE :oldSlugSlash')
                    ->andWhere('resource_key != :historyResourceKey')
                    ->andWhere('id != :changedRouteId')
                    ->setParameter('oldSlugSlash', $oldSlug . '/%', ParameterType::STRING)
                    ->setParameter('locale', $locale, ParameterType::STRING)
                    ->setParameter('historyResourceKey', Route::HISTORY_RESOURCE_KEY, ParameterType::STRING)
                    ->setParameter('changedRouteId', $route->getId(), ParameterType::INTEGER);

                if (\is_string($webspace)) {
                    $updateQueryBuilder->setParameter('webspace', $webspace, ParameterType::STRING);
                }

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
