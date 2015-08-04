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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;

/**
 * Repository for the UserSettings, implementing some additional functions
 * for querying objects.
 */
class UserSettingRepository extends EntityRepository implements UserSettingRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSettingsByKeyAndValue($key, $value)
    {
        if(is_array($value)){
           $value = json_encode($value);
        }

        try {
            $qb = $this->createQueryBuilder('setting')
                ->where('setting.key=:key')
                ->andWhere('setting.value=:value');

            $query = $qb->getQuery();
            $query->setParameter('key', $key);
            $query->setParameter('value', $value);

            return $query->getResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }
}
