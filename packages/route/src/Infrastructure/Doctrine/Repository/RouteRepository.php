<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Doctrine\Repository;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @phpstan-import-type RouteFilter from RouteRepositoryInterface
 * @phpstan-import-type RouteSortBy from RouteRepositoryInterface
 */
class RouteRepository implements RouteRepositoryInterface
{
    /**
     * @var EntityRepository<Route>
     */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $this->entityManager->getRepository(Route::class);
    }

    public function add(Route $route): void
    {
        $this->entityManager->persist($route);
    }

    public function findOneBy(array $filters): ?Route
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->select('route');

        // Hydrate Object is default, but we need to specify it here to make PHPStan happy:
        //     see: https://github.com/phpstan/phpstan-doctrine?tab=readme-ov-file#supported-methods
        /** @var Route */
        return $queryBuilder->getQuery()->getOneOrNullResult(Query::HYDRATE_OBJECT);
    }

    public function findFirstBy(array $filters, array $sortBys = []): ?Route
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBys);
        $queryBuilder->select('route');
        $queryBuilder->setMaxResults(1);

        // Hydrate Object is default, but we need to specify it here to make PHPStan happy:
        //     see: https://github.com/phpstan/phpstan-doctrine?tab=readme-ov-file#supported-methods
        /** @var Route */
        return $queryBuilder->getQuery()->getOneOrNullResult(Query::HYDRATE_OBJECT);
    }

    public function existBy(array $filters): bool
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('route.id');

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    public function findBy(array $filters, array $sortBys = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->select('route');

        // Hydrate Object is default, but we need to specify it here to make PHPStan happy:
        //     see: https://github.com/phpstan/phpstan-doctrine?tab=readme-ov-file#supported-methods
        /** @var iterable<Route> */
        return $queryBuilder->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }

    /**
     * @param RouteFilter $filters
     * @param RouteSortBy $sortBys
     */
    protected function createQueryBuilder(array $filters, array $sortBys = []): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('route');

        if (\array_key_exists('site', $filters)) {
            $site = $filters['site'] ?? null;
            $queryBuilder->andWhere(
                null === $site ? 'route.site IS NULL' : 'route.site = :site'
            );

            if (null !== $site) {
                $queryBuilder->setParameter('site', $site);
            }
        }

        if (\array_key_exists('siteOrNull', $filters)) {
            $site = $filters['siteOrNull'] ?? null;
            $queryBuilder->andWhere(
                null === $site ? 'route.site IS NULL' : '(route.site = :site OR route.site IS NULL)'
            );

            if (null !== $site) {
                $queryBuilder->setParameter('site', $site);
            }
        }

        $locale = $filters['locale'] ?? null;
        if (null !== $locale) {
            $queryBuilder->andWhere('route.locale = :locale')
                ->setParameter('locale', $locale);
        }

        $locales = $filters['locales'] ?? null;
        if (null !== $locales) {
            $queryBuilder->andWhere('route.locale IN (:locales)')
                ->setParameter('locales', $locales);
        }

        $slug = $filters['slug'] ?? null;
        if (null !== $slug) {
            $queryBuilder->andWhere('route.slug = :slug')
                ->setParameter('slug', $slug);
        }

        $resourceKey = $filters['resourceKey'] ?? null;
        if (null !== $resourceKey) {
            $queryBuilder->andWhere('route.resourceKey = :resourceKey')
                ->setParameter('resourceKey', $resourceKey);
        }

        $resourceId = $filters['resourceId'] ?? null;
        if (null !== $resourceId) {
            $queryBuilder->andWhere('route.resourceId = :resourceId')
                ->setParameter('resourceId', $resourceId);
        }

        $excludeResource = $filters['excludeResource'] ?? null;
        if (null !== $excludeResource) {
            $expr = $queryBuilder->expr();

            $queryBuilder
                ->andWhere($expr->not($expr->andX(
                    $expr->eq('route.resourceKey', ':excludeResourceKey'),
                    $expr->eq('route.resourceId', ':excludeResourceId')
                )))
                ->setParameter('excludeResourceKey', $excludeResource['resourceKey'])
                ->setParameter('excludeResourceId', $excludeResource['resourceId']);
        }

        if ([] !== $sortBys) {
            foreach ($sortBys as $field => $order) {
                $order = match (true) {
                    // if we filter by siteOrNull and order by site we need invert the order for specific platforms
                    // TODO if possible in future use something like ASC NULLS FIRST / DESC NULLS LAST directly
                    ('site' === $field // @phpstan-ignore-line identical.alwaysTrue
                        && (
                            $this->entityManager->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform
                            || $this->entityManager->getConnection()->getDatabasePlatform() instanceof OraclePlatform
                        )
                        && \array_key_exists('siteOrNull', $filters)
                    ) => match ($order) {
                        'asc' => 'desc',
                        'desc' => 'asc',
                    },
                    default => $order,
                };

                $queryBuilder->addOrderBy(\sprintf('route.%s', $field), $order);
            }
        }

        return $queryBuilder;
    }
}
