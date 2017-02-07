<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
}
