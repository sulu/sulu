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
use Sulu\Component\Security\Authentication\RoleSettingInterface;

/**
 * Repository for the Role-Settings, implementing some additional functions for querying objects.
 */
class RoleSettingRepository extends EntityRepository
{
    /**
     * Returns value of given role-setting.
     *
     * @param int $roleId
     * @param string $key
     *
     * @return mixed|null
     */
    public function findSettingValue($roleId, $key)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.value')
            ->join('s.role', 'r')
            ->where('r.id = :roleId')
            ->andWhere('s.key = :key')
            ->setParameters(['roleId' => $roleId, 'key' => $key]);

        try {
            return json_decode($queryBuilder->getQuery()->getSingleScalarResult(), true);
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * Returns role-setting object.
     *
     * @param int $roleId
     * @param string $key
     *
     * @return RoleSettingInterface|null
     */
    public function findSetting($roleId, $key)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->join('s.role', 'r')
            ->where('r.id = :roleId')
            ->andWhere('s.key = :key')
            ->setParameters(['roleId' => $roleId, 'key' => $key]);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }
}
