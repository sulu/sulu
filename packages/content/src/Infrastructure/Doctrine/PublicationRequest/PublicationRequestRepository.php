<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Doctrine\PublicationRequest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Domain\Exception\PublicationRequestNotFoundException;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequest;
use Sulu\Content\Domain\Repository\PublicationRequestRepositoryInterface;

final class PublicationRequestRepository implements PublicationRequestRepositoryInterface
{
    /**
     * @var EntityRepository<PublicationRequest>
     */
    private readonly EntityRepository $entityRepository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->entityRepository = $entityManager->getRepository(PublicationRequest::class);
    }

    public function getOneBy(array $filters): PublicationRequest
    {
        try {
            /** @var PublicationRequest $publicationRequest */
            $publicationRequest = $this->createQueryBuilder($filters)->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new PublicationRequestNotFoundException($filters, 0, $e);
        }

        return $publicationRequest;
    }

    public function findOneBy(array $filters): ?PublicationRequest
    {
        try {
            /** @var PublicationRequest $publicationRequest */
            $publicationRequest = $this->createQueryBuilder($filters)->getQuery()->getSingleResult();
        } catch (NoResultException) {
            return null;
        }

        return $publicationRequest;
    }

    public function findBy(array $filters = [], array $sortBy = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        /** @var iterable<PublicationRequest> $results */
        $results = $queryBuilder->getQuery()->getResult();

        foreach ($results as $result) {
            yield $result;
        }
    }

    public function countBy(array $filters = []): int
    {
        unset($filters['page']); // @phpstan-ignore-line
        unset($filters['limit']); // @phpstan-ignore-line

        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->select('COUNT(DISTINCT publicationRequest.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function add(PublicationRequest $publicationRequest): void
    {
        $this->entityManager->persist($publicationRequest);
    }

    public function remove(PublicationRequest $publicationRequest): void
    {
        $this->entityManager->remove($publicationRequest);
    }

    /**
     * @param array{
     *     id?: string,
     *     ids?: string[],
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     requestedAt?: 'asc'|'desc',
     * } $sortBy
     */
    private function createQueryBuilder(array $filters, array $sortBy = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('publicationRequest');

        $id = $filters['id'] ?? null;
        if (null !== $id) {
            $queryBuilder->andWhere('publicationRequest.id = :id')
                ->setParameter('id', $id);
        }

        $ids = $filters['ids'] ?? null;
        if (null !== $ids) {
            $queryBuilder->andWhere('publicationRequest.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $resourceKey = $filters['resourceKey'] ?? null;
        if (null !== $resourceKey) {
            $queryBuilder->andWhere('publicationRequest.resourceKey = :resourceKey')
                ->setParameter('resourceKey', $resourceKey);
        }

        $resourceId = $filters['resourceId'] ?? null;
        if (null !== $resourceId) {
            $queryBuilder->andWhere('publicationRequest.resourceId = :resourceId')
                ->setParameter('resourceId', $resourceId);
        }

        $locale = $filters['locale'] ?? null;
        if (null !== $locale) {
            $queryBuilder->andWhere('publicationRequest.locale = :locale')
                ->setParameter('locale', $locale);
        }

        $active = $filters['active'] ?? null;
        if (null !== $active) {
            if ($active) {
                $queryBuilder->andWhere('publicationRequest.activeKey IS NOT NULL');
            } else {
                $queryBuilder->andWhere('publicationRequest.activeKey IS NULL');
            }
        }

        $page = $filters['page'] ?? null;
        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
            if (null !== $page) {
                $queryBuilder->setFirstResult(($page - 1) * $limit);
            }
        }

        $requestedAtSort = $sortBy['requestedAt'] ?? null;
        if (null !== $requestedAtSort) {
            $queryBuilder->addOrderBy('publicationRequest.requestedAt', $requestedAtSort);
        }

        return $queryBuilder;
    }
}
