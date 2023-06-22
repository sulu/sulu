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

namespace Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

final class ReferenceRepository implements ReferenceRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<ReferenceInterface>
     */
    private $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->entityRepository = $this->entityManager->getRepository(ReferenceInterface::class);
    }

    public function create(
        string $resourceKey,
        string $resourceId,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        string $referenceProperty,
        array $referenceViewAttributes = [],
    ): ReferenceInterface {
        /** @var class-string<ReferenceInterface> $className */
        $className = $this->entityRepository->getClassName();

        /** @var ReferenceInterface $reference */
        $reference = new $className();

        $reference
            ->setResourceKey($resourceKey)
            ->setResourceId($resourceId)
            ->setReferenceLocale($referenceLocale)
            ->setReferenceResourceKey($referenceResourceKey)
            ->setReferenceResourceId($referenceResourceId)
            ->setReferenceTitle($referenceTitle)
            ->setReferenceViewAttributes($referenceViewAttributes)
            ->setReferenceProperty($referenceProperty)
            ->setReferenceCount(1)
            ->setReferenceLiveCount(1);

        return $reference;
    }

    public function add(ReferenceInterface $reference): void
    {
        $this->entityManager->persist($reference);
    }

    public function remove(ReferenceInterface $reference): void
    {
        $this->entityManager->remove($reference);
    }

    public function removeBy(array $filters): void
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder
            ->delete(ReferenceInterface::class, 'reference');

        $queryBuilder->getQuery()->execute();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * @param array{
     *     referenceResourceKey?: string,
     *     referenceResourceId?: string,
     *     referenceLocale?: string,
     * } $filters
     */
    private function createQueryBuilder(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('reference');

        $referenceResourceKey = $filters['referenceResourceKey'] ?? null;
        if (null !== $referenceResourceKey) {
            $queryBuilder->andWhere('reference.referenceResourceKey = :referenceResourceKey')
                ->setParameter('referenceResourceKey', $referenceResourceKey);
        }

        $referenceResourceId = $filters['referenceResourceId'] ?? null;
        if (null !== $referenceResourceId) {
            $queryBuilder->andWhere('reference.referenceResourceId = :referenceResourceId')
                ->setParameter('referenceResourceId', $referenceResourceId);
        }

        $referenceLocale = $filters['referenceLocale'] ?? null;
        if (null !== $referenceLocale) {
            $queryBuilder->andWhere('reference.referenceLocale = :referenceLocale')
                ->setParameter('referenceLocale', $referenceLocale);
        }

        return $queryBuilder;
    }
}
