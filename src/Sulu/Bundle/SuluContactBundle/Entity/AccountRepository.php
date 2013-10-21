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
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Doctrine\ORM\Query;

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

    /**
     * Get account by id
     * @param $id
     * @return mixed
     */
    public function findAccountById($id)
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.addresses', 'addresses')
                ->leftJoin('addresses.country', 'country')
                ->leftJoin('addresses.addressType', 'addressType')
                ->leftJoin('account.parent', 'parent')
                ->leftJoin('account.urls', 'urls')
                ->leftJoin('urls.urlType', 'urlType')
                ->leftJoin('account.phones', 'phones')
                ->leftJoin('phones.phoneType', 'phoneType')
                ->leftJoin('account.emails', 'emails')
                ->leftJoin('emails.emailType', 'emailType')
                ->leftJoin('account.notes', 'notes')
                ->addSelect('addresses')
                ->addSelect('addressType')
                ->addSelect('country')
                ->addSelect('parent')
                ->addSelect('urls')
                ->addSelect('urlType')
                ->addSelect('phones')
                ->addSelect('phoneType')
                ->addSelect('emails')
                ->addSelect('emailType')
                ->addSelect('notes')
                ->where('account.id=:accountId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();


        } catch (NoResultException $ex) {
            return null;
        }
    }
}
