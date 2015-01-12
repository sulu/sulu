<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class RoleRepository extends EntityRepository
{
    /**
     * Finds a role with a specific id
     *
     * @param $id ID of the role
     * @return role
     */
    public function findRoleById($id)
    {
        try {

            $qb = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->leftJoin('role.securityType', 'securityType')
                ->addSelect('permissions')
                ->addSelect('securityType')
                ->where('role.id=:roleId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('roleId', $id);

            return $query->getSingleResult();

        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * Searches for all roles
     *
     * @return array
     */
    public function findAllRoles()
    {
        try {

            $qb = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->addSelect('permissions');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

            $result = $query->getResult();

            return $result;

        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * Return an array containing the names of all the roles
     *
     * @return array
     */
    public function getRoleNames()
    {
        $query = $this->createQueryBuilder('role')
            ->select('role.name')
            ->getQuery();

        $roles = array();
        foreach ($query->getArrayResult() as $roleEntity) {
            $roles[] = $roleEntity['name'];
        }

        return $roles;
    }
}
