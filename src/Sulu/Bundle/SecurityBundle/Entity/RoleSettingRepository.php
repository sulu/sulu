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
use Sulu\Component\Security\Authentication\RoleSettingInterface;
use Sulu\Component\Security\Authentication\RoleSettingRepositoryInterface;

/**
 * Repository for the Role-Settings, implementing some additional functions for querying objects.
 *
 * @extends EntityRepository<RoleSettingInterface>
 */
class RoleSettingRepository extends EntityRepository implements RoleSettingRepositoryInterface
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
            ->setParameter('roleId', $roleId)
            ->setParameter('key', $key);

        try {
            /** @var string $value */
            $value = $queryBuilder->getQuery()->getSingleScalarResult();

            return \json_decode($value, true);
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
            ->setParameter('roleId', $roleId)
            ->setParameter('key', $key);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }
}
