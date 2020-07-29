<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 */
class RoleRepository extends EntityRepository implements RoleRepositoryInterface
{
    public function findRoleById($id)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->leftJoin('role.securityType', 'securityType')
                ->addSelect('permissions')
                ->addSelect('securityType')
                ->where('role.id=:roleId');

            $query = $queryBuilder->getQuery();
            $query->setParameter('roleId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    public function findRoleByNameAndSystem($name, $system)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->leftJoin('role.securityType', 'securityType')
                ->addSelect('permissions')
                ->addSelect('securityType')
                ->where('role.name=:roleName')
                ->andWhere('role.system=:roleSystem');

            $query = $queryBuilder->getQuery();
            $query->setParameter('roleName', $name);
            $query->setParameter('roleSystem', $system);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    public function findAllRoles()
    {
        try {
            $queryBuilder = $this->createQueryBuilder('role')
                ->leftJoin('role.permissions', 'permissions')
                ->addSelect('permissions');

            $query = $queryBuilder->getQuery();

            return $query->getResult();
        } catch (NoResultException $e) {
            return;
        }
    }

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

    public function findRoleIdsBySystem($system)
    {
        $result = $this->createQueryBuilder('role')
            ->select('role.id')
            ->where('role.system = :system')
            ->setParameter('system', $system)
            ->getQuery()
            ->getResult();

        return \array_map(function($role) {
            return $role['id'];
        }, $result);
    }
}
