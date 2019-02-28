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
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * Contains special queries to find routes.
 */
class RouteRepository extends EntityRepository implements RouteRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function findByEntity($entityClass, $entityId, $locale)
    {
        $query = $this->createQueryBuilder('entity')
            ->andWhere('entity.entityClass = :entityClass')
            ->andWhere('entity.entityId = :entityId')
            ->andWhere('entity.locale = :locale')
            ->andWhere('entity.history = false')
            ->getQuery()
            ->setParameters(['entityClass' => $entityClass, 'entityId' => $entityId, 'locale' => $locale]);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findHistoryByEntity($entityClass, $entityId, $locale)
    {
        $query = $this->createQueryBuilder('entity')
            ->andWhere('entity.entityClass = :entityClass')
            ->andWhere('entity.entityId = :entityId')
            ->andWhere('entity.locale = :locale')
            ->andWhere('entity.history = true')
            ->getQuery()
            ->setParameters(['entityClass' => $entityClass, 'entityId' => $entityId, 'locale' => $locale]);

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
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

        return $queryBuilder->getQuery()->getResult();
    }
}
