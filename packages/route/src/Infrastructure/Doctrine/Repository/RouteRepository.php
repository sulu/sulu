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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @phpstan-import-type RouteFilter from RouteRepositoryInterface
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

    public function existBy(array $filters): bool
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('route.id');

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    public function findBy(array $filters): iterable
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
     */
    protected function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('route');

        if (\array_key_exists('site', $filters)) {
            $site = $filters['site'] ?? null;
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

        return $queryBuilder;
    }
}
