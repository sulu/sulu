<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class UserRepository extends EntityRepository
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


}
