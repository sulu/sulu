<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 */
class RoleRepository extends EntityRepository implements RoleRepositoryInterface
{
    /**
     * Finds a role with a specific id.
     *
     * @param int $id ID of the role
     *
     * @return RoleInterface
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
            $query->setParameter('roleId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Finds a role with a specific name.
     *
     * @param string $name
     * @param string $system
     *
     * @return RoleInterface|null
     */
    public function findRoleByNameAndSystem($name, $system)
    {
        try {
            $qb = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->leftJoin('role.securityType', 'securityType')
                ->addSelect('permissions')
                ->addSelect('securityType')
                ->where('role.name=:roleName')
                ->andWhere('role.system=:roleSystem');

            $query = $qb->getQuery();
            $query->setParameter('roleName', $name);
            $query->setParameter('roleSystem', $system);

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
    public function findAllRoles()
    {
        try {
            $qb = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->addSelect('permissions');

            $query = $qb->getQuery();

            return $query->getResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Return an array containing the names of all the roles.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $query = $this->createQueryBuilder('role')
            ->select('role.name')
            ->getQuery();

        $roles = [];
        foreach ($query->getArrayResult() as $roleEntity) {
            $roles[] = $roleEntity['name'];
        }

        return $roles;
    }
}
