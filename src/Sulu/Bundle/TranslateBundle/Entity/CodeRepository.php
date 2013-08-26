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
            ->join('u.package', 'p')
            ->join('u.translations', 't')
            ->join('t.catalogue', 'c')
            ->addSelect('u')
            ->addSelect('p')
            ->addSelect('t')
            ->addSelect('c');
        // add order by
        foreach ($sorting as $k => $d) {
            $qb->addOrderBy('u.' . $k, $d);
        }
        // add pagination
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        // if needed add where statements
        if (sizeof($where) > 0) {
            $and = $qb->expr()->andX();
            foreach ($where as $k => $v) {
                $and->add($qb->expr()->eq($k, $v));
            }
            $qb->where($and);
        }

        $query = $qb->getQuery();
        return $query->getArrayResult();
    }
}