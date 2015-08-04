<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Repository;

use Doctrine\ORM\EntityManager;


/**
 * Repository for combined entities account and contact.
 */
class AccountContactRepository implements AccountContactRepositoryInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $contactEntityName;

    /**
     * @var string
     */
    private $accountEntityName;

    public function __construct(EntityManager $entityManager, $contactEntityName, $accountEntityName)
    {
        $this->entityManager = $entityManager;
        $this->contactEntityName = $contactEntityName;
        $this->accountEntityName = $accountEntityName;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($filters, $page, $pageSize)
    {
        // TODO
        // * tagOperator
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->addSelect('c')
            ->from($this->contactEntityName, 'c')
            ->from($this->accountEntityName, 'a');

        if (isset($filters['tags']) && sizeof($filters['tags']) > 0 && strtolower($filters['tagOperator']) === 'or') {
            $queryBuilder->join('c.tags', 'ctags')
                ->join('a.tags', 'atags')
                ->where('ctags.id IN (:tags)')
                ->orwhere('atags.id IN (:tags)');
        }

        $query = $queryBuilder->getQuery();
        if (isset($filters['tags'])) {
            $query->setParameter('tags', $filters['tags']);
        }

        if ($page !== null && $pageSize > 0) {
            $query->setMaxResults($pageSize);
            $query->setFirstResult($page * $pageSize);
        }

        return $query->getResult();
    }
}
