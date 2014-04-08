<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Listing;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class ListRepository extends EntityRepository
{
    /**
     * @var ListRestHelper
     */
    private $helper;

    /**
     * @var string
     */
    private $dbDriver;

    /**
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param ListRestHelper $helper
     * @param string $dbDriver
     */
    public function __construct(ObjectManager $em, ClassMetadata $class, ListRestHelper $helper, $dbDriver)
    {
        parent::__construct($em, $class);
        $this->helper = $helper;
        $this->dbDriver = $dbDriver;
    }

    /**
     * Find list with parameter
     *
     * @param array $where
     * @param string $prefix
     * @param bool $justCount Defines, if find should just return the total number of results
     * @return array|object|int
     */
    public function find($where = array(), $prefix = 'u', $justCount = false)
    {
        $searchPattern = $this->helper->getSearchPattern();
        $searchFields = $this->helper->getSearchFields();

        // if search string is set, but search fields are not, take all fields into account
        if (!is_null($searchPattern) && $searchPattern != '' && (is_null($searchFields) || count($searchFields) == 0)) {
            $searchFields = $this->getEntityManager()->getClassMetadata($this->getEntityName())->getFieldNames();
        }

        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getClassMetadata()->getFieldNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $searchFields,
            $this->dbDriver
        );

        if ($justCount) {
            $queryBuilder->justCount('u.id');
        }

        $dql = $queryBuilder->find($prefix);

        $query = $this->getEntityManager()
            ->createQuery($dql);

        if (!$justCount) {
            $query->setFirstResult($this->helper->getOffset())
                ->setMaxResults($this->helper->getLimit());
        }
        if ($searchPattern != null && $searchPattern != '') {
            $query->setParameter('search', '%' . $searchPattern . '%');
        }

        // if just used for counting
        if ($justCount) {
            return intval($query->getSingleResult()['totalcount']);
        }

        return $query->getArrayResult();
    }

    /**
     * returns the amount of data
     * @param array $where
     * @param string $prefix
     * @return int
     */
    public function getCount($where = array(), $prefix = 'u')
    {
        return $this->find($where, $prefix, true);
    }
}
