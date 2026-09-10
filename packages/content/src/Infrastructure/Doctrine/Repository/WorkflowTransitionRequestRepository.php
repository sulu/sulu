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

namespace Sulu\Content\Infrastructure\Doctrine\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestNotFoundException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestCheck;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestCheckStatusEnum;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;

/**
 * @phpstan-type WorkflowTransitionRequestFilters array{
 *     id?: string,
 *     resourceKey?: string,
 *     resourceId?: string,
 *     locale?: string,
 *     active?: bool,
 * }
 */
final class WorkflowTransitionRequestRepository implements WorkflowTransitionRequestRepositoryInterface
{
    private const ALLOWED_FILTER_KEYS = ['id', 'resourceKey', 'resourceId', 'locale', 'active'];

    /**
     * @var EntityRepository<WorkflowTransitionRequest>
     */
    private readonly EntityRepository $entityRepository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->entityRepository = $entityManager->getRepository(WorkflowTransitionRequest::class);
    }

    public function getOneBy(array $filters): WorkflowTransitionRequest
    {
        try {
            /** @var WorkflowTransitionRequest $workflowTransitionRequest */
            $workflowTransitionRequest = $this->createQueryBuilder($filters)->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new WorkflowTransitionRequestNotFoundException($filters, 0, $e);
        }

        return $workflowTransitionRequest;
    }

    public function findOneBy(array $filters): ?WorkflowTransitionRequest
    {
        try {
            /** @var WorkflowTransitionRequest $workflowTransitionRequest */
            $workflowTransitionRequest = $this->createQueryBuilder($filters)->getQuery()->getSingleResult();
        } catch (NoResultException) {
            return null;
        }

        return $workflowTransitionRequest;
    }

    public function countBy(array $filters = []): int
    {
        $queryBuilder = $this->createQueryBuilder($filters);
        $queryBuilder->select('COUNT(workflowTransitionRequest.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findFlatBy(array $filters, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->joinCreator($this->createQueryBuilder($filters))
            ->orderBy('workflowTransitionRequest.created', 'DESC')
            // `created` only holds seconds, the UUIDv7 id breaks the tie in the same order.
            ->addOrderBy('workflowTransitionRequest.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        // The status is derived from the reviewer rows by the aggregate, so the rows are projected
        // from hydrated entities instead of selected as scalars.
        /** @var list<WorkflowTransitionRequest> $workflowTransitionRequests */
        $workflowTransitionRequests = $queryBuilder->getQuery()->getResult();

        $this->preloadChecksAndApprovals($workflowTransitionRequests);

        return \array_map(
            static fn (WorkflowTransitionRequest $workflowTransitionRequest) => [
                'id' => $workflowTransitionRequest->getId(),
                'requester' => $workflowTransitionRequest->getCreator()?->getFullName(),
                'status' => $workflowTransitionRequest->getStatus()->value,
            ],
            $workflowTransitionRequests,
        );
    }

    /**
     * Loaded in one extra query: joining them into the paged query would break `setMaxResults`.
     *
     * @param list<WorkflowTransitionRequest> $workflowTransitionRequests
     */
    private function preloadChecksAndApprovals(array $workflowTransitionRequests): void
    {
        if ([] === $workflowTransitionRequests) {
            return;
        }

        $this->entityRepository->createQueryBuilder('workflowTransitionRequest')
            ->addSelect('checks', 'approvals')
            ->leftJoin('workflowTransitionRequest.checks', 'checks')
            ->leftJoin('workflowTransitionRequest.approvals', 'approvals')
            ->where('workflowTransitionRequest.id IN (:ids)')
            ->setParameter('ids', \array_map(
                static fn (WorkflowTransitionRequest $workflowTransitionRequest) => $workflowTransitionRequest->getId(),
                $workflowTransitionRequests,
            ))
            ->getQuery()
            ->getResult();
    }

    public function add(WorkflowTransitionRequest $workflowTransitionRequest): void
    {
        $this->entityManager->persist($workflowTransitionRequest);
    }

    public function settleCheck(
        WorkflowTransitionRequestCheck $check,
        WorkflowTransitionRequestCheckStatusEnum $status,
        ?string $comment,
    ): void {
        $decidedAt = new \DateTimeImmutable();
        $tableName = $this->entityManager->getClassMetadata(WorkflowTransitionRequestCheck::class)->getTableName();

        $claimed = (int) $this->entityManager->getConnection()->executeStatement(
            \sprintf(
                'UPDATE %s SET status = :status, comment = :comment, decided_at = :decidedAt
                    WHERE id = :id AND status = :pending',
                $tableName,
            ),
            [
                'status' => $status->value,
                'comment' => $comment,
                'decidedAt' => $decidedAt,
                'id' => $check->getId(),
                'pending' => WorkflowTransitionRequestCheckStatusEnum::PENDING->value,
            ],
            ['decidedAt' => Types::DATETIME_IMMUTABLE],
        );

        if (1 !== $claimed) {
            return;
        }

        // The statement bypasses the unit of work, so the hydrated row would otherwise stay `pending`
        // for the rest of an inline run and the response would contradict the database.
        $check->settle($status, $comment, $decidedAt);
    }

    /**
     * Kept out of createQueryBuilder(): countBy() would join for nothing, and a to-many join there
     * would fan the rows out and count pairs instead of requests.
     */
    private function joinCreator(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->addSelect('creator', 'creatorContact')
            ->leftJoin('workflowTransitionRequest.creator', 'creator')
            ->leftJoin('creator.contact', 'creatorContact');
    }

    /**
     * @param WorkflowTransitionRequestFilters $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        // Every filter narrows, so an unknown key would widen the result instead of failing.
        $unknownKeys = \array_diff(\array_keys($filters), self::ALLOWED_FILTER_KEYS);
        if ([] !== $unknownKeys) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown filter key(s) "%s", allowed are: %s.',
                \implode('", "', $unknownKeys),
                \implode(', ', self::ALLOWED_FILTER_KEYS),
            ));
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('workflowTransitionRequest');

        $id = $filters['id'] ?? null;
        if (null !== $id) {
            $queryBuilder->andWhere('workflowTransitionRequest.id = :id')
                ->setParameter('id', $id);
        }

        $resourceKey = $filters['resourceKey'] ?? null;
        if (null !== $resourceKey) {
            $queryBuilder->andWhere('workflowTransitionRequest.resourceKey = :resourceKey')
                ->setParameter('resourceKey', $resourceKey);
        }

        $resourceId = $filters['resourceId'] ?? null;
        if (null !== $resourceId) {
            $queryBuilder->andWhere('workflowTransitionRequest.resourceId = :resourceId')
                ->setParameter('resourceId', $resourceId);
        }

        $locale = $filters['locale'] ?? null;
        if (null !== $locale) {
            $queryBuilder->andWhere('workflowTransitionRequest.locale = :locale')
                ->setParameter('locale', $locale);
        }

        $active = $filters['active'] ?? null;
        if (null !== $active) {
            if ($active) {
                $queryBuilder->andWhere('workflowTransitionRequest.activeKey IS NOT NULL');
            } else {
                $queryBuilder->andWhere('workflowTransitionRequest.activeKey IS NULL');
            }
        }

        return $queryBuilder;
    }
}
