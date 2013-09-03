<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for the Codes, implementing some additional functions
 * for querying objects
 */
class CodeRepository extends EntityRepository
{
    /**
     * Searches Entities by where clauses, pagination and sorted
     * @param integer|null $limit Page size for Pagination
     * @param integer|null $offset Offset for Pagination
     * @param array|null $sorting Columns to sort
     * @param array|null $where Where clauses
     * @return array Results
     */
    public function findGetAll($limit = null, $offset = null, $sorting = null, $where = array())
    {
        // create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.package', 'p')
            ->leftJoin('u.translations', 't')
            ->leftJoin('t.catalogue', 'c')
            ->addSelect('p')
            ->addSelect('t')
            ->addSelect('c');

        $qb = $this->addSorting($qb, $sorting, 'u');
        $qb = $this->addPagination($qb, $offset, $limit);

        // if needed add where statements
        if (is_array($where) && sizeof($where) > 0) {
            $qb = $this->addWhere($qb, $where);
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    public function findByCatalogue($catalogueId)
    {
        $dql = 'SELECT c, t
                FROM  Sulu\Bundle\TranslateBundle\Entity\Code c
                    LEFT JOIN c.package p
                    LEFT JOIN p.catalogues ca
                    LEFT JOIN c.translations t WITH t.catalogue = ca
                WHERE ca.id = :id';

        $query = $this->getEntityManager()
            ->createQuery($dql);

        return $query->setParameter('id', $catalogueId)->getArrayResult();
    }

    public function findByPackage($packageId)
    {
        $dql = 'SELECT c, t
                FROM  Sulu\Bundle\TranslateBundle\Entity\Code c
                    LEFT JOIN c.package p
                    LEFT JOIN p.catalogues ca
                    LEFT JOIN c.translations t WITH t.catalogue = ca
                WHERE p.id = :id';

        $query = $this->getEntityManager()
            ->createQuery($dql);

        return $query->setParameter('id', $packageId)->getArrayResult();
    }

    /**
     * Add sorting to querybuilder
     * @param QueryBuilder $qb
     * @param array $sorting
     * @param string $prefix
     * @return QueryBuilder
     */
    private function addSorting($qb, $sorting, $prefix = 'u')
    {
        // add order by
        foreach ($sorting as $k => $d) {
            $qb->addOrderBy($prefix . '.' . $k, $d);
        }

        return $qb;
    }

    /**
     * add pagination to querybuilder
     * @param QueryBuilder $qb
     * @param integer|null $limit Page size for Pagination
     * @param integer|null $offset Offset for Pagination
     * @return QueryBuilder
     */
    private function addPagination($qb, $offset, $limit)
    {
        // add pagination
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb;
    }

    /**
     * add where to querybuilder
     * @param QueryBuilder $qb
     * @param array $where
     * @return QueryBuilder
     */
    private function addWhere($qb, $where)
    {
        $and = $qb->expr()->andX();
        foreach ($where as $k => $v) {
            $and->add($qb->expr()->eq($k, $v));
        }
        $qb->where($and);

        return $qb;
    }
}
