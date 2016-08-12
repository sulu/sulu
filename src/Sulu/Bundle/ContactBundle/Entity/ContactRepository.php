<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Contact\Model\ContactRepositoryInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

/**
 * Repository for the contacts, implementing some additional functions for querying objects.
 */
class ContactRepository extends EntityRepository implements DataProviderRepositoryInterface, ContactRepositoryInterface
{
    use DataProviderRepositoryTrait;

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        // Create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->leftJoin('account.mainContact', 'mainContact')
            ->leftJoin('u.contactAddresses', 'contactAddresses')
            ->leftJoin('contactAddresses.address', 'addresses')
            ->leftJoin('addresses.country', 'country')
            ->leftJoin('addresses.addressType', 'addressType')
            ->leftJoin('u.locales', 'locales')
            ->leftJoin('u.emails', 'emails')
            ->leftJoin('emails.emailType', 'emailType')
            ->leftJoin('u.faxes', 'faxes')
            ->leftJoin('faxes.faxType', 'faxType')
            ->leftJoin('u.notes', 'notes')
            ->leftJoin('u.phones', 'phones')
            ->leftJoin('phones.phoneType', 'phoneType')
            ->leftJoin('u.tags', 'tags')
            ->leftJoin('u.urls', 'urls')
            ->leftJoin('urls.urlType', 'urlType')
            ->leftJoin('u.title', 'title')
            ->leftJoin('accountContacts.position', 'position')
            ->leftJoin('u.medias', 'medias')
            ->leftJoin('u.categories', 'categories')
            ->leftJoin('categories.translations', 'categoryTranslations')
            ->leftJoin('u.bankAccounts', 'bankAccounts')
            ->addSelect('categoryTranslations')
            ->addSelect('position')
            ->addSelect('title')
            ->addSelect('accountContacts')
            ->addSelect('mainContact')
            ->addSelect('account')
            ->addSelect('urls')
            ->addSelect('partial tags.{id,name}')
            ->addSelect('locales')
            ->addSelect('emails')
            ->addSelect('emailType')
            ->addSelect('faxes')
            ->addSelect('faxType')
            ->addSelect('phones')
            ->addSelect('phoneType')
            ->addSelect('addresses')
            ->addSelect('contactAddresses')
            ->addSelect('country')
            ->addSelect('addressType')
            ->addSelect('notes')
            ->addSelect('urlType')
            ->addSelect('medias')
            ->addSelect('categories')
            ->addSelect('bankAccounts')
            ->where('u.id=:id');

        $query = $qb->getQuery();
        $query->setParameter('id', $id);

        try {
            $contact = $query->getSingleResult();

            return $contact;
        } catch (NoResultException $nre) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds($ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        // Create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->leftJoin('account.mainContact', 'mainContact')
            ->leftJoin('u.contactAddresses', 'contactAddresses')
            ->leftJoin('contactAddresses.address', 'addresses')
            ->leftJoin('addresses.country', 'country')
            ->leftJoin('addresses.addressType', 'addressType')
            ->leftJoin('u.locales', 'locales')
            ->leftJoin('u.emails', 'emails')
            ->leftJoin('emails.emailType', 'emailType')
            ->leftJoin('u.faxes', 'faxes')
            ->leftJoin('faxes.faxType', 'faxType')
            ->leftJoin('u.notes', 'notes')
            ->leftJoin('u.phones', 'phones')
            ->leftJoin('phones.phoneType', 'phoneType')
            ->leftJoin('u.tags', 'tags')
            ->leftJoin('u.urls', 'urls')
            ->leftJoin('urls.urlType', 'urlType')
            ->leftJoin('u.title', 'title')
            ->leftJoin('accountContacts.position', 'position')
            ->leftJoin('u.medias', 'medias')
            ->leftJoin('u.categories', 'categories')
            ->leftJoin('categories.translations', 'categoryTranslations')
            ->leftJoin('u.bankAccounts', 'bankAccounts')
            ->addSelect('categoryTranslations')
            ->addSelect('position')
            ->addSelect('title')
            ->addSelect('accountContacts')
            ->addSelect('mainContact')
            ->addSelect('account')
            ->addSelect('urls')
            ->addSelect('partial tags.{id,name}')
            ->addSelect('locales')
            ->addSelect('emails')
            ->addSelect('emailType')
            ->addSelect('faxes')
            ->addSelect('faxType')
            ->addSelect('phones')
            ->addSelect('phoneType')
            ->addSelect('addresses')
            ->addSelect('contactAddresses')
            ->addSelect('country')
            ->addSelect('addressType')
            ->addSelect('notes')
            ->addSelect('urlType')
            ->addSelect('medias')
            ->addSelect('categories')
            ->addSelect('bankAccounts')
            ->where('u.id IN (:ids)')
            ->orderBy('u.id', 'ASC');

        $query = $qb->getQuery();
        $query->setParameter('ids', $ids);

        try {
            return $query->getResult();
        } catch (NoResultException $nre) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByIdAndDelete($id)
    {
        // Create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->leftJoin('u.contactAddresses', 'contactAddresses')
            ->leftJoin('contactAddresses.address', 'addresses')
            ->leftJoin('addresses.contactAddresses', 'addressContactAddresses')
            ->leftJoin('addresses.accountAddresses', 'addressAccountAddresses')
            ->leftJoin('addressContactAddresses.contact', 'addressContacts')
            ->leftJoin('addressAccountAddresses.account', 'addressAccounts')
            ->leftJoin('addresses.country', 'country')
            ->leftJoin('addresses.addressType', 'addressType')
            ->leftJoin('u.locales', 'locales')
            ->leftJoin('u.emails', 'emails')
            ->leftJoin('emails.contacts', 'emailsContacts')
            ->leftJoin('emails.accounts', 'emailsAccounts')
            ->leftJoin('emails.emailType', 'emailType')
            ->leftJoin('u.notes', 'notes')
            ->leftJoin('u.phones', 'phones')
            ->leftJoin('phones.contacts', 'phonesContacts')
            ->leftJoin('phones.accounts', 'phonesAccounts')
            ->leftJoin('phones.phoneType', 'phoneType')
            ->leftJoin('u.faxes', 'faxes')
            ->leftJoin('faxes.contacts', 'faxesContacts')
            ->leftJoin('faxes.accounts', 'faxesAccounts')
            ->leftJoin('faxes.faxType', 'faxType')
            ->leftJoin('u.tags', 'tags')
            ->leftJoin('u.urls', 'urls')
            ->leftJoin('u.title', 'title')
            ->leftJoin('accountContacts.position', 'position')
            ->addSelect('position')
            ->addSelect('title')
            ->addSelect('urls')
            ->addSelect('tags')
            ->addSelect('accountContacts')
            ->addSelect('account')
            ->addSelect('locales')
            ->addSelect('emails')
            ->addSelect('emailType')
            ->addSelect('faxes')
            ->addSelect('faxType')
            ->addSelect('phones')
            ->addSelect('phoneType')
            ->addSelect('contactAddresses')
            ->addSelect('addressContactAddresses')
            ->addSelect('addressAccountAddresses')
            ->addSelect('addresses')
            ->addSelect('country')
            ->addSelect('addressType')
            ->addSelect('emailsContacts')
            ->addSelect('faxesContacts')
            ->addSelect('phonesContacts')
            ->addSelect('addressContacts')
            ->addSelect('emailsAccounts')
            ->addSelect('faxesAccounts')
            ->addSelect('phonesAccounts')
            ->addSelect('addressAccounts')
            ->addSelect('notes')
            ->where('u.id=:id');

        $query = $qb->getQuery();
        $query->setParameter('id', $id);

        try {
            $contact = $query->getSingleResult();

            return $contact;
        } catch (NoResultException $nre) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findGetAll($limit = null, $offset = null, $sorting = ['id' => 'asc'], $where = [])
    {
        // Create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.emails', 'emails')
            ->leftJoin('u.phones', 'phones')
            ->leftJoin('u.faxes', 'faxes')
            ->leftJoin('u.contactAddresses', 'contactAddresses')
            ->leftJoin('contactAddresses.address', 'addresses')
            ->leftJoin('u.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->leftJoin('u.title', 'title')
            ->addSelect('title')
            ->addSelect('emails')
            ->addSelect('phones')
            ->addSelect('faxes')
            ->addSelect('contactAddresses')
            ->addSelect('addresses');

        $qb = $this->addSorting($qb, $sorting, 'u');
        $qb = $this->addPagination($qb, $offset, $limit);

        // If needed add where statements
        if (is_array($where) && count($where) > 0) {
            $qb = $this->addWhere($qb, $where, 'u');
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByAccountId(
        $accountId,
        $excludeContactId = null,
        $arrayResult = true,
        $onlyFetchMainAccounts = true
    ) {
        $qb = $this->createQueryBuilder('c');

        // Only fetch main accounts
        if ($onlyFetchMainAccounts) {
            $qb->join('c.accountContacts', 'accountContacts', 'WITH', 'accountContacts.main = true');
        } else {
            $qb->join('c.accountContacts', 'accountContacts');
        }
        $qb->join('accountContacts.account', 'account', 'WITH', 'account.id = :accountId')
            ->setParameter('accountId', $accountId);

        if (!is_null($excludeContactId)) {
            $qb->where('c.id != :excludeId')
                ->setParameter('excludeId', $excludeContactId);
        }

        $query = $qb->getQuery();

        if ($arrayResult) {
            return $query->getArrayResult();
        } else {
            return $query->getResult();
        }
    }

    /**
     * Add sorting to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param array $sorting
     * @param string $prefix
     *
     * @return QueryBuilder
     */
    private function addSorting($qb, $sorting, $prefix = 'u')
    {
        // Add order by
        foreach ($sorting as $k => $d) {
            $qb->addOrderBy($prefix . '.' . $k, $d);
        }

        return $qb;
    }

    /**
     * add pagination to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param int|null $limit Page size for Pagination
     * @param int|null $offset Offset for Pagination
     *
     * @return QueryBuilder
     */
    private function addPagination($qb, $offset, $limit)
    {
        // Add pagination
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb;
    }

    /**
     * add where to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param array $where
     * @param string $prefix
     *
     * @return QueryBuilder
     */
    private function addWhere($qb, $where, $prefix = '')
    {
        $prefix = $prefix !== '' ? $prefix . '.' : '';
        $and = $qb->expr()->andX();
        foreach ($where as $k => $v) {
            $and->add($qb->expr()->eq($prefix . $k, "'" . $v . "'"));
        }
        $qb->where($and);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function findByCriteriaEmailAndPhone($where, $email = null, $phone = null)
    {

        // Create basic query
        $qb = $this->createQueryBuilder('contact')
            ->leftJoin('contact.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->addSelect('accountContacts')
            ->addSelect('account');

        if (isset($where['id'])) {
            $qb->andWhere('contact.id = :id');
            $qb->setParameter('id', $where['id']);
        }
        if (isset($where['firstName'])) {
            $qb->andWhere('contact.firstName = :firstName');
            $qb->setParameter('firstName', $where['firstName']);
        }
        if (isset($where['lastName'])) {
            $qb->andWhere('contact.lastName= :lastName');
            $qb->setParameter('lastName', $where['lastName']);
        }
        if (!is_null($email)) {
            $qb->join('contact.emails', 'emails', 'WITH', 'emails.email = :email');
            $qb->setParameter('email', $email);
        }
        if (!is_null($phone)) {
            $qb->join('contact.phones', 'phones', 'WITH', 'phones.phone = :phone');
            $qb->setParameter('phone', $phone);
        }

        try {
            $query = $qb->getQuery();
            $result = $query->getSingleResult();
        } catch (NoResultException $nre) {
            return;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findContactWithAccountsById($id)
    {
        // Create basic query
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.accountContacts', 'accountContacts')
            ->leftJoin('accountContacts.account', 'account')
            ->addSelect('accountContacts')
            ->addSelect('account')
            ->where('c.id=:id')
            ->orderBy('accountContacts.main', 'DESC');

        $query = $qb->getQuery();
        $query->setParameter('id', $id);

        try {
            $contact = $query->getSingleResult();

            return $contact;
        } catch (NoResultException $nre) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder->addSelect('emails')
            ->addSelect('emailType')
            ->addSelect('phones')
            ->addSelect('phoneType')
            ->addSelect('faxes')
            ->addSelect('faxType')
            ->addSelect('urls')
            ->addSelect('urlType')
            ->addSelect('tags')
            ->addSelect('categories')
            ->addSelect('translations')
            ->leftJoin($alias . '.emails', 'emails')
            ->leftJoin('emails.emailType', 'emailType')
            ->leftJoin($alias . '.phones', 'phones')
            ->leftJoin('phones.phoneType', 'phoneType')
            ->leftJoin($alias . '.faxes', 'faxes')
            ->leftJoin('faxes.faxType', 'faxType')
            ->leftJoin($alias . '.urls', 'urls')
            ->leftJoin('urls.urlType', 'urlType')
            ->leftJoin($alias . '.tags', 'tags')
            ->leftJoin($alias . '.categories', 'categories')
            ->leftJoin('categories.translations', 'translations');
    }
}
