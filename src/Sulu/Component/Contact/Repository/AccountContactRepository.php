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
        $parameter = array();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->addSelect('c')
            ->distinct()
            ->from($this->contactEntityName, 'c')
            ->from($this->accountEntityName, 'a');

        if (isset($filters['tags']) && sizeof($filters['tags']) > 0 && strtolower($filters['tagOperator']) === 'or') {
            $queryBuilder->join('c.tags', 'ctags')
                ->join('a.tags', 'atags')
                ->where('ctags.id IN (:tags)')
                ->orwhere('atags.id IN (:tags)');

            $parameter['tags'] = $filters['tags'];
        }

        // works if there are at least one account and contact with this tags
        // if only contact/account has this tags they will not show ...
        if (isset($filters['tags']) && sizeof($filters['tags']) > 0 && strtolower($filters['tagOperator']) === 'and') {
            $contactExpr = $queryBuilder->expr()->andX();
            $accountExpr = $queryBuilder->expr()->andX();

            $len = sizeof($filters['tags']);
            for ($i = 0; $i < $len; $i++) {
                $queryBuilder->join('c.tags', 'ctags' . $i)
                    ->join('a.tags', 'atags' . $i);

                $contactExpr->add($queryBuilder->expr()->eq('ctags' . $i . '.id', ':tag' . $i));
                $accountExpr->add($queryBuilder->expr()->eq('atags' . $i . '.id', ':tag' . $i));

                $parameter['tag' . $i] = $filters['tags'][$i];
            }
            $queryBuilder->andWhere($queryBuilder->expr()->andX($contactExpr, $accountExpr));
        }

        $query = $queryBuilder->getQuery();
        foreach ($parameter as $name => $value) {
            $query->setParameter($name, $value);
        }

        if ($page !== null && $pageSize > 0) {
            $query->setMaxResults($pageSize + 1);
            $query->setFirstResult($page * $pageSize);
        }

        return $query->getResult();
    }
}
