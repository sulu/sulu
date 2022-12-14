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
use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;
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
        string $title,
        string $locale,
        string $property,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceTitle,
        ?string $securityContext = null,
        ?string $securityObjectType = null,
        ?string $securityObjectId = null,
        ?string $referenceSecurityContext = null,
        ?string $referenceSecurityObjectType = null,
        ?string $referenceSecurityObjectId = null
    ): ReferenceInterface {
        /** @var class-string<ReferenceInterface> $className */
        $className = $this->entityRepository->getClassName();

        /** @var ReferenceInterface $reference */
        $reference = new $className();

        $reference
            ->setResourceKey($resourceKey)
            ->setResourceId($resourceId)
            ->setTitle($title)
            ->setLocale($locale)
            ->setSecurityContext($securityContext)
            ->setSecurityObjectType($securityObjectType)
            ->setSecurityObjectId($securityObjectId)
            ->setReferenceResourceKey($referenceResourceKey)
            ->setReferenceResourceId($referenceResourceId)
            ->setReferenceTitle($referenceTitle)
            ->setReferenceSecurityContext($referenceSecurityContext)
            ->setReferenceSecurityObjectType($referenceSecurityObjectType)
            ->setReferenceSecurityObjectId($referenceSecurityObjectId)
            ->setProperty($property)
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

    public function removeByReferenceResourceKeyAndId(string $referenceResourceKey, string $referenceResourceId, string $locale): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->delete(ReferenceInterface::class, 'reference')
            ->where('reference.referenceResourceKey = :resourceKey')
            ->andWhere('reference.referenceResourceId = :resourceId')
            ->andWhere('reference.locale = :locale')
            ->setParameter('resourceKey', $referenceResourceKey)
            ->setParameter('resourceId', $referenceResourceId)
            ->setParameter('locale', $locale);

        $queryBuilder->getQuery()->execute();
    }

    public function getOneBy(array $criteria): ReferenceInterface
    {
        $reference = $this->findOneBy($criteria);

        if (null === $reference) {
            throw new ReferenceNotFoundException($criteria);
        }

        return $reference;
    }

    public function findOneBy(array $criteria): ?ReferenceInterface
    {
        return $this->entityRepository->findOneBy($criteria);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
