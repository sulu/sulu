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
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Sulu\Route\Domain\Model\Route;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class RouteChangedUpdater implements ResetInterface
{
    /**
     * @var array<int, array{oldSlug: string, oldSite: string|null, route: Route}>
     */
    private array $routeChanges = [];

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

        $oldSite = $route->getSite();
        if ($args->hasChangedField('site')) {
            $oldSite = $args->getOldValue('site');
            \assert(\is_string($oldSite) || \is_null($oldSite), 'Site is expected to be always a string or null.');
        }

        if ($oldSlug === $route->getSlug()) {
            return;
        }

        $this->routeChanges[$route->getId()] = [
            'oldSlug' => $oldSlug,
            'oldSite' => $oldSite,
            'route' => $route,
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (0 === \count($this->routeChanges)) {
            return;
        }

        $connection = $args->getObjectManager()->getConnection();

        $routesTableName = $args->getObjectManager()->getClassMetadata(Route::class)->getTableName();

        foreach ($this->routeChanges as $routeChange) {
            $route = $routeChange['route'];
            $oldSlug = $routeChange['oldSlug'];
            $oldSite = $routeChange['oldSite'];
            $newSlug = $route->getSlug();
            $locale = $route->getLocale();
            $site = $route->getSite();

            // select all child and grand routes of oldSlug
            $selectQueryBuilder = $connection->createQueryBuilder()
                ->from($routesTableName, 'parent')
                ->select('parent.id AS parent_id')
                ->addSelect('child.site')
                ->addSelect('child.slug')
                ->addSelect('child.resource_key')
                ->addSelect('child.resource_id')
                ->innerJoin('parent', $routesTableName, 'child', 'child.parent_id = parent.id')
                ->andWhere(\is_string($site) ? 'parent.site = :site' : 'parent.site IS NULL')
                ->andWhere('parent.locale = :locale')
                ->andWhere('(parent.slug = :newSlug OR parent.slug LIKE :oldSlugSlash)') // direct child is using newSlug already updated as we are in PostFlush, grand child use oldSlugWithSlash as not yet updated
                ->setParameter('newSlug', $newSlug, ParameterType::STRING)
                ->setParameter('oldSlugSlash', $oldSlug . '/%', ParameterType::STRING)
                ->setParameter('locale', $locale, ParameterType::STRING);

            if (\is_string($site)) {
                $selectQueryBuilder->setParameter('site', $site, ParameterType::STRING);
            }

            /**
             * @var array<int, array{
             *     parent_id: int,
             *     site: string|null,
             *     slug: string,
             *     resource_key: string,
             *     resource_id: string,
             * }> $childAndGrandChildResult
             */
            $childAndGrandChildResult = $selectQueryBuilder->executeQuery()->fetchAllAssociative();
            $parentIds = [];
            $childAndGrandChildHistoryUrls = [];
            foreach ($childAndGrandChildResult as $childAndGrandChildRow) {
                $parentIds[] = $childAndGrandChildRow['parent_id'];
                $childAndGrandChildHistoryUrls[] = [
                    'site' => $childAndGrandChildRow['site'],
                    'locale' => $locale,
                    'slug' => $childAndGrandChildRow['slug'],
                    // TODO we currently handling history URLs as own
                    'resourceKey' => Route::HISTORY_RESOURCE_KEY,
                    'resourceId' => $childAndGrandChildRow['resource_key'] . '::' . $childAndGrandChildRow['resource_id'],
                ];
            }

            $parentIds = \array_filter($parentIds);
            $parentIds = \array_unique($parentIds); // DISTINCT and GROUP BY is a lot slower as make it unique in PHP itself

            // create route history for changed route
            $historyInsertQueryBuilder = $connection->createQueryBuilder()->insert($routesTableName)
                ->values([
                    // history never has parents ad they never will be updated
                    'site' => ':site',
                    'locale' => ':locale',
                    'slug' => ':slug',
                    // TODO we currently handling history URLs as own
                    'resource_key' => ':resourceKey',
                    'resource_id' => ':resourceId',
                ])
                ->setParameters([
                    'site' => $oldSite,
                    'locale' => $locale,
                    'slug' => $oldSlug,
                    // TODO we currently handling history URLs as own
                    'resourceKey' => Route::HISTORY_RESOURCE_KEY,
                    'resourceId' => $route->getResourceKey() . '::' . $route->getResourceId(),
                ]);

            $historyInsertQueryBuilder->executeStatement();

            if (0 === \count($parentIds)) {
                continue;
            }

            $newSlugCast = '';
            if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                $newSlugCast = '::text'; // concat seems not directly supported by dbal and parameter $1 (newSlug) is not cast to text correctly. So manually cast it here: https://github.com/sulu/sulu/pull/7726#discussion_r1930324013
            }

            // update child and grand routes
            $updateQueryBuilder = $connection->createQueryBuilder()
                ->update($routesTableName, 'r')
                ->set('slug', 'CONCAT(:newSlug' . $newSlugCast . ', SUBSTRING(slug, ' . (\strlen($oldSlug) + 1) . '))')
                ->setParameter('newSlug', $newSlug, ParameterType::STRING)
                ->where('parent_id IN (:parentIds)')
                ->setParameter('parentIds', $parentIds, ArrayParameterType::INTEGER);

            $updateQueryBuilder->executeStatement();

            // create child and grand history routes
            foreach ($childAndGrandChildHistoryUrls as $childAndGrandChildHistoryUrl) {
                $historyInsertQueryBuilder = $connection->createQueryBuilder()->insert($routesTableName)
                    ->values([
                        // history never has parents as they never will be updated
                        'site' => ':site',
                        'locale' => ':locale',
                        'slug' => ':slug',
                        // TODO we currently handling history URLs as own
                        'resource_key' => ':resourceKey',
                        'resource_id' => ':resourceId',
                    ])
                    ->setParameters($childAndGrandChildHistoryUrl);

                $historyInsertQueryBuilder->executeStatement();
            }
        }
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->routeChanges = [];
    }
}
