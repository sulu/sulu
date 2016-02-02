<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;

class AccessControlRepository extends EntityRepository implements AccessControlRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findByTypeAndIdAndRole($type, $id, $roleId)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('accessControl')
                ->leftJoin('accessControl.role', 'role')
                ->where('accessControl.entityId = :entityId')
                ->andWhere('accessControl.entityClass = :entityClass')
                ->andWhere('role.id = :roleId');

            $query = $queryBuilder->getQuery()
                ->setParameter('entityId', $id)
                ->setParameter('entityClass', $type)
                ->setParameter('roleId', $roleId);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByTypeAndId($type, $id)
    {
        $queryBuilder = $this->createQueryBuilder('accessControl')
            ->leftJoin('accessControl.role', 'role')
            ->where('accessControl.entityId = :entityId')
            ->andWhere('accessControl.entityClass = :entityClass');

        $query = $queryBuilder->getQuery()
            ->setParameter('entityId', $id)
            ->setParameter('entityClass', $type);

        return $query->getResult();
    }
}
