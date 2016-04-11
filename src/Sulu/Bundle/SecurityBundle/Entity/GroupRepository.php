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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 */
class GroupRepository extends EntityRepository
{
    /**
     * Searches for a role with a specific id.
     *
     * @param $id
     *
     * @return Group
     */
    public function findGroupById($id)
    {
        try {
            $qb = $this->createQueryBuilder('grp')
                ->leftJoin('grp.roles', 'roles')
                ->leftJoin('grp.parent', 'parent')
                ->addSelect('roles')
                ->addSelect('parent')
                ->where('grp.id=:groupId');

            $query = $qb->getQuery();
            $query->setParameter('groupId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Searches for all roles.
     *
     * @return array
     */
    public function findAllGroups()
    {
        try {
            $qb = $this->createQueryBuilder('grp');
            $query = $qb->getQuery();

            $result = $query->getResult();

            return $result;
        } catch (NoResultException $ex) {
            return;
        }
    }
}
