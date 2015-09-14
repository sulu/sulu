<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Util\SortByIdsTrait;

/**
 * Implements functionality for the manager of account and contact combination.
 */
class CustomerManager implements CustomerManagerInterface
{
    use SortByIdsTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $contactEntityClass;

    /**
     * @var string
     */
    private $accountEntityClass;

    public function __construct(EntityManager $entityManager, $contactEntityClass, $accountEntityClass)
    {
        $this->entityManager = $entityManager;
        $this->contactEntityClass = $contactEntityClass;
        $this->accountEntityClass = $accountEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds($ids)
    {
        $parsed = $this->parseIds($ids);

        $accounts = $this->findAccountsByIds($parsed['accounts']);
        $contacts = $this->findContactsByIds($parsed['contacts']);

        return $this->sortByIds($ids, array_merge($accounts, $contacts));
    }

    /**
     * Returns array of accounts by ids.
     *
     * @param array $ids
     *
     * @return array
     */
    private function findAccountsByIds($ids)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('CONCAT(\'a\', a.id) AS id, a.name AS name')
            ->from($this->accountEntityClass, 'a')
            ->where('a.id IN (:ids)');

        $query = $queryBuilder->getQuery();
        $query->setParameter('ids', $ids);

        return $query->getArrayResult();
    }

    /**
     * Returns array of contacts by ids.
     *
     * @param array $ids
     *
     * @return array
     */
    private function findContactsByIds($ids)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('CONCAT(\'c\', c.id) AS id, CONCAT(CONCAT(c.firstName, \' \'), c.lastName) AS name')
            ->from($this->contactEntityClass, 'c')
            ->where('c.id IN (:ids)');

        $query = $queryBuilder->getQuery();
        $query->setParameter('ids', $ids);

        return $query->getArrayResult();
    }

    /**
     * Splits ids into contact and account ids.
     *
     * @param array $ids
     *
     * @return array
     */
    private function parseIds($ids)
    {
        $contacts = [];
        $accounts = [];

        foreach ($ids as $id) {
            $type = substr($id, 0, 1);
            if ($type === 'c') {
                $contacts[] = substr($id, 1);
            } elseif ($type === 'a') {
                $accounts[] = substr($id, 1);
            }
        }

        return ['contacts' => $contacts, 'accounts' => $accounts];
    }
}
