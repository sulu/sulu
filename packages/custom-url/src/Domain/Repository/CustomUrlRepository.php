<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Domain\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepositoryInterface;

/**
 * @extends ServiceEntityRepository<CustomUrlInterface>
 */
class CustomUrlRepository extends ServiceEntityRepository implements CustomUrlRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CustomUrlRouteRepositoryInterface $customUrlRouteRepository,
    ) {
        parent::__construct($registry, CustomUrl::class);
    }

    public function createNew(?string $uuid): CustomUrlInterface
    {
        return new CustomUrl($uuid);
    }

    public function add(CustomUrlInterface $customUrl): void
    {
        $this->_em->persist($customUrl);
        $this->customUrlRouteRepository->addRoute($customUrl);
    }

    public function findByUrlNewestPublished(string $url, ?string $locale = null): ?CustomUrlInterface
    {
        /** @var CustomUrlInterface|null $customUrl */
        $customUrl = $this->createQueryBuilder('c')
            ->join('c.routes', 'r')
            ->andWhere('c.targetDocument IS NOT NULL')
            ->andWhere('c.published = 1')
            ->andWhere('r.path = :route')
            ->setParameter('route', $url)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $customUrl;
    }

    public function findByTarget(UuidBehavior $page): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.targetDocument = :id')
            ->setParameter('id', $page->getUuid());

        if ($page instanceof WebspaceBehavior) {
            $qb->andWhere('c.webspace = :webspace')
                ->setParameter('webspace', $page->getWebspaceName());
        }

        /** @var array<CustomUrlInterface> $result */
        $result = $qb->getQuery()->execute();

        return $result;
    }

    public function remove(CustomUrlInterface $customUrl): void
    {
        $this->_em->remove($customUrl);
    }
}
