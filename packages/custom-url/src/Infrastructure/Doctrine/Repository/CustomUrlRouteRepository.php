<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;

class CustomUrlRouteRepository implements CustomUrlRouteRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findBy(array $filters = [], array $sortBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        /** @var iterable<CustomUrlRouteInterface> $result */
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function findOneBy(array $filters): ?CustomUrlRouteInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, []);
        $queryBuilder->setMaxResults(1);

        /** @var CustomUrlRouteInterface|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function remove(CustomUrlRouteInterface $route): void
    {
        $this->entityManager->remove($route);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     path?: string,
     *     history?: bool,
     *     customUrl?: string,
     * } $filters
     * @param array{} $sortBy
     */
    private function createQueryBuilder(array $filters, array $sortBy): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('route')
            ->from(CustomUrlRouteInterface::class, 'route');

        if (isset($filters['uuid'])) {
            $queryBuilder->andWhere('route.uuid = :uuid')
                ->setParameter('uuid', $filters['uuid']);
        }

        if (isset($filters['path'])) {
            $queryBuilder->andWhere('route.path = :path')
                ->setParameter('path', $filters['path']);
        }

        if (isset($filters['history'])) {
            $queryBuilder->andWhere('route.history = :history')
                ->setParameter('history', $filters['history']);
        }

        if (isset($filters['customUrl'])) {
            $queryBuilder->join('route.customUrl', 'customUrl')
                ->andWhere('customUrl.uuid = :customUrlUuid')
                ->setParameter('customUrlUuid', $filters['customUrl']);
        }

        return $queryBuilder;
    }
}
