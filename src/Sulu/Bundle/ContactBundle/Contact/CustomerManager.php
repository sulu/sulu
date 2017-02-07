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
use Sulu\Bundle\ContactBundle\Util\IdConverterInterface;
use Sulu\Bundle\ContactBundle\Util\IndexComparatorInterface;

/**
 * Implements functionality for the manager of account and contact combination.
 */
class CustomerManager implements CustomerManagerInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var IdConverterInterface
     */
    private $converter;

    /**
     * @var IndexComparatorInterface
     */
    private $comparator;

    /**
     * @var string
     */
    private $contactEntityClass;

    /**
     * @var string
     */
    private $accountEntityClass;

    public function __construct(
        EntityManager $entityManager,
        IdConverterInterface $converter,
        IndexComparatorInterface $comparator,
        $contactEntityClass,
        $accountEntityClass
    ) {
        $this->entityManager = $entityManager;
        $this->converter = $converter;
        $this->comparator = $comparator;
        $this->contactEntityClass = $contactEntityClass;
        $this->accountEntityClass = $accountEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds($ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $parsed = $this->converter->convertIdsToGroupedIds($ids, ['a' => [], 'c' => []]);

        $accounts = $this->findAccountsByIds($parsed['a']);
        $contacts = $this->findContactsByIds($parsed['c']);

        $result = array_merge($accounts, $contacts);
        // the @ is necessary in case of a PHP bug https://bugs.php.net/bug.php?id=50688
        @usort(
            $result,
            function ($a, $b) use ($ids) {
                return $this->comparator->compare($a['id'], $b['id'], $ids);
            }
        );

        return $result;
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
        if (count($ids) === 0) {
            return [];
        }

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
        if (count($ids) === 0) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('CONCAT(\'c\', c.id) AS id, CONCAT(CONCAT(c.firstName, \' \'), c.lastName) AS name')
            ->from($this->contactEntityClass, 'c')
            ->where('c.id IN (:ids)');

        $query = $queryBuilder->getQuery();
        $query->setParameter('ids', $ids);

        return $query->getArrayResult();
    }
}
