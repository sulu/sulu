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
    public function findOneByContactId($contactId)
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.contacts', 'c', 'WITH', 'c.id = :contactId')
            ->setParameter('contactId', $contactId);
        $query = $qb->getQuery();

        return $query->getSingleResult();
    }

    /**
     * Get account by id
     * @param $id
     * @param $contacts
     * @return mixed
     */
    public function findAccountById($id, $contacts = false)
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
                ->leftJoin('account.faxes', 'faxes')
                ->leftJoin('faxes.faxType', 'faxType')
                ->leftJoin('account.bankAccounts', 'bankAccounts')
                ->addSelect('bankAccounts')
                ->addSelect('addresses')
                ->addSelect('country')
                ->addSelect('addressType')
                ->addSelect('parent')
                ->addSelect('urls')
                ->addSelect('urlType')
                ->addSelect('phones')
                ->addSelect('phoneType')
                ->addSelect('emails')
                ->addSelect('emailType')
                ->addSelect('faxes')
                ->addSelect('faxType')
                ->addSelect('notes')
                ->where('account.id = :accountId');


            if ($contacts === true) {
                $qb->leftJoin('account.contacts', 'contacts')
                ->addSelect('contacts');
            }

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * finds all accounts but only selects given fields
     * @param array $fields
     * @return array
     */
    public function findAllSelect($fields = array())
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->from($this->getEntityName(), 'account');

        foreach ($fields as $field) {
            $qb->addSelect('account.' . $field . ' AS ' . $field);
        }

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getArrayResult();
    }

    /**
     * Get account by id to delete
     * @param $id
     * @return mixed
     */
    public function findAccountByIdAndDelete($id)
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.addresses', 'addresses')
                ->leftJoin('account.children', 'children')
                ->leftJoin('account.contacts', 'contacts')
                ->leftJoin('addresses.country', 'country')
                ->leftJoin('addresses.addressType', 'addressType')
                ->leftJoin('addresses.contacts', 'addressContacts')
                ->leftJoin('addresses.accounts', 'addressAccounts')
                ->leftJoin('account.parent', 'parent')
                ->leftJoin('account.urls', 'urls')
                ->leftJoin('urls.urlType', 'urlType')
                ->leftJoin('account.phones', 'phones')
                ->leftJoin('phones.contacts', 'phonesContacts')
                ->leftJoin('phones.accounts', 'phonesAccounts')
                ->leftJoin('phones.phoneType', 'phoneType')
                ->leftJoin('account.faxes', 'faxes')
                ->leftJoin('faxes.faxType', 'faxType')
                ->leftJoin('faxes.accounts', 'faxesAccounts')
                ->leftJoin('faxes.contacts', 'faxesContacts')
                ->leftJoin('account.emails', 'emails')
                ->leftJoin('emails.emailType', 'emailType')
                ->leftJoin('emails.contacts', 'emailsContacts')
                ->leftJoin('emails.accounts', 'emailsAccounts')
                ->leftJoin('account.notes', 'notes')
                ->leftJoin('account.bankAccounts', 'bankAccounts')
                ->addSelect('bankAccounts')
                ->addSelect('addresses')
                ->addSelect('children')
                ->addSelect('contacts')
                ->addSelect('addressType')
                ->addSelect('country')
                ->addSelect('parent')
                ->addSelect('urls')
                ->addSelect('urlType')
                ->addSelect('phones')
                ->addSelect('phoneType')
                ->addSelect('emails')
                ->addSelect('emailType')
                ->addSelect('faxes')
                ->addSelect('faxType')
                ->addSelect('faxesContacts')
                ->addSelect('emailsContacts')
                ->addSelect('phonesContacts')
                ->addSelect('addressContacts')
                ->addSelect('faxesAccounts')
                ->addSelect('emailsAccounts')
                ->addSelect('phonesAccounts')
                ->addSelect('addressAccounts')
                ->addSelect('notes')
                ->where('account.id = :accountId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }
}
