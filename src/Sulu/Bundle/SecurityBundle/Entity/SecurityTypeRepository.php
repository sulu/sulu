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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 *
 * @extends EntityRepository<SecurityType>
 */
class SecurityTypeRepository extends EntityRepository
{
    /**
     * Searches for a role with a specific id.
     *
     * @return SecurityType|null
     */
    public function findSecurityTypeById($id)
    {
        try {
            $qb = $this->createQueryBuilder('securityType')
                ->where('securityType.id=:securityTypeId');

            $query = $qb->getQuery();
            $query->setParameter('securityTypeId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }
}
