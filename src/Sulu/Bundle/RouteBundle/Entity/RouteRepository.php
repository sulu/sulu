<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Entity;

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * Contains special queries to find routes.
 *
 * @extends EntityRepository<RouteInterface>
 */
class RouteRepository extends EntityRepository implements RouteRepositoryInterface
{
    public function findByPath($path, $locale)
    {
        $query = $this->createQueryBuilder('entity')
            ->addSelect('target')
            ->leftJoin('entity.target', 'target')
            ->andWhere('entity.path = :path')
            ->andWhere('entity.locale = :locale')
            ->getQuery()
            ->setParameters(['path' => $path, 'locale' => $locale]);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    public function findByEntity($entityClass, $entityId, $locale)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->andWhere('entity.entityClass = :entityClass')
            ->andWhere('entity.entityId = :entityId')
            ->andWhere('entity.locale = :locale')
            ->andWhere('entity.history = false')
            ->setParameters(['entityClass' => $entityClass, 'entityId' => $entityId, 'locale' => $locale]);

        try {
            /** @var RouteInterface */
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findHistoryByEntity($entityClass, $entityId, $locale)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->andWhere('entity.entityClass = :entityClass')
            ->andWhere('entity.entityId = :entityId')
            ->andWhere('entity.locale = :locale')
            ->andWhere('entity.history = true')
            ->setParameters(['entityClass' => $entityClass, 'entityId' => $entityId, 'locale' => $locale]);

        /** @var RouteInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    public function findAllByEntity($entityClass, $entityId, $locale = null)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->andWhere('entity.entityClass = :entityClass')
            ->andWhere('entity.entityId = :entityId')
            ->setParameters(['entityClass' => $entityClass, 'entityId' => $entityId]);

        if ($locale) {
            $queryBuilder
                ->andWhere('entity.locale = :locale')
                ->setParameter('locale', $locale);
        }

        /** @var RouteInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    public function persist(RouteInterface $route)
    {
        $this->getEntityManager()->persist($route);
    }

    public function remove(RouteInterface $route)
    {
        $this->getEntityManager()->remove($route);
    }
}
