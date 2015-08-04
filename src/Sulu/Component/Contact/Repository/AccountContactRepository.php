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
    public function findBy($filters, $limit, $page, $pageSize)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->addSelect('a')
            ->from($this->contactEntityName, 'c')
            ->join('c.tags', 'ctags')
            ->from($this->accountEntityName, 'a')
            ->join('a.tags', 'atags')
            ->where('ctags.id IN (:tags)')
            ->orwhere('atags.id IN (:tags)');

        $query = $queryBuilder->getQuery();
        $query->setParameter('tags', $filters['tags']);

        return $query->getResult();
    }
}
