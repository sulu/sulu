<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException;

/**
 * Repository for the Codes, implementing some additional functions
 * for querying objects
 */
class AccountRepository extends EntityRepository
{
    /**
     * Searches for accounts with a specific contact
     * @param $contactId
     * @return array
     */
    public function findOneByContactId($contactId) {
        $qb = $this->createQueryBuilder('a')
            ->join('a.contacts','c', 'WITH', 'c.id = :contactId')
            ->setParameter('contactId', $contactId);
        $query = $qb->getQuery();

        return $query->getSingleResult();
    }
}
