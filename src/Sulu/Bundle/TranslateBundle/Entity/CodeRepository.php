<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for the Codes, implementing some additional functions
 * for querying objects.
 */
class CodeRepository extends EntityRepository
{
    /**
     * returns code with given ID.
     *
     * @param $id
     *
     * @return Code|null
     */
    public function getCodeById($id)
    {
        try {
            $qb = $this->createQueryBuilder('code')
                ->leftJoin('code.translations', 'translations')
                ->leftJoin('code.location', 'location')
                ->leftJoin('code.package', 'package')
                ->addSelect('translations')
                ->addSelect('location')
                ->addSelect('package')
                ->where('code.id=:codeId');

            $query = $qb->getQuery();
            $query->setParameter('codeId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Searches Entities by where clauses, pagination and sorted.
     *
     * @param int|null   $limit   Page size for Pagination
     * @param int|null   $offset  Offset for Pagination
     * @param array|null $sorting Columns to sort
     * @param array|null $where   Where clauses
     *
     * @return array Results
     */
    public function findGetAll($limit = null, $offset = null, $sorting = null, $where = [])
    {
        // create basic query
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.package', 'p')
            ->leftJoin('u.translations', 't')
            ->leftJoin('t.catalogue', 'c')
            ->addSelect('p')
            ->addSelect('t')
            ->addSelect('c');

        if ($sorting) {
            $qb = $this->addSorting($qb, $sorting, 'u');
        }
        $qb = $this->addPagination($qb, $offset, $limit);

        // if needed add where statements
        if (is_array($where) && count($where) > 0) {
            $qb = $this->addWhere($qb, $where);
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * returns array of codes filtered by catalogue.
     *
     * @param $catalogueId
     *
     * @return array
     */
    public function findByCatalogue($catalogueId)
    {
        $dql = 'SELECT c, t
                FROM  Sulu\Bundle\TranslateBundle\Entity\Code c
                    LEFT JOIN c.package p
                    LEFT JOIN p.catalogues ca
                    LEFT JOIN c.translations t WITH t.catalogue = ca
                WHERE ca.id = :id
                    ORDER BY c.id';

        $query = $this->getEntityManager()
            ->createQuery($dql);

        return $query->setParameter('id', $catalogueId)->getArrayResult();
    }

    /**
     * returns array of codes filtered by catalogue with the suggestion of default language.
     *
     * @param $catalogueId
     *
     * @return array
     */
    public function findByCatalogueWithSuggestion($catalogueId)
    {
        // FIXME Don't use sub queries
        $dql = 'SELECT c, t
                FROM Sulu\Bundle\TranslateBundle\Entity\Code c
                    LEFT JOIN c.package p
                    LEFT JOIN p.catalogues ca
                    LEFT JOIN c.translations t WITH t.catalogue = ca
                WHERE ca.id = :id OR ca.id in (
                    SELECT ca2.id
                                FROM Sulu\Bundle\TranslateBundle\Entity\Catalogue ca1
                                    LEFT JOIN ca1.package p1
                                    LEFT JOIN p1.catalogues ca2
                                WHERE ca1.id = :id AND ca2.isDefault = :isDefault
                )';

        $query = $this->getEntityManager()
            ->createQuery($dql);

        return $query
            ->setParameter('isDefault', true)
            ->setParameter('id', $catalogueId)
            ->getArrayResult();
    }

    /**
     * returns a array of codes filtered by package.
     *
     * @param $packageId
     *
     * @return array
     */
    public function findByPackage($packageId)
    {
        $dql = 'SELECT c, t
                FROM  Sulu\Bundle\TranslateBundle\Entity\Code c
                    LEFT JOIN c.package p
                    LEFT JOIN p.catalogues ca
                    LEFT JOIN c.translations t WITH t.catalogue = ca
                WHERE p.id = :id
                    ORDER BY c.id';

        $query = $this->getEntityManager()
            ->createQuery($dql);

        return $query->setParameter('id', $packageId)->getArrayResult();
    }

    /**
     * Add sorting to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param array        $sorting
     * @param string       $prefix
     *
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
     * add pagination to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param int|null     $limit  Page size for Pagination
     * @param int|null     $offset Offset for Pagination
     *
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
     * add where to querybuilder.
     *
     * @param QueryBuilder $qb
     * @param array        $where
     *
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
