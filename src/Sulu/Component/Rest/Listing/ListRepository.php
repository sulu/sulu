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

use Symfony\Component\HttpFoundation\Request;
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
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param ListRestHelper $helper
     */
    public function __construct(ObjectManager $em, ClassMetadata $class, ListRestHelper $helper)
    {
        parent::__construct($em, $class);
        $this->helper = $helper;
    }

    /**
     * Find list with parameter
     *
     * @param array $where
     * @param string $prefix
     * @return array|object
     */
    public function find($where = array(), $prefix = 'u')
    {
        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $this->helper->getSearchFields()
        );

        $dql = $queryBuilder->find($prefix);

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setFirstResult($this->helper->getOffset())
            ->setMaxResults($this->helper->getLimit());
        if ($this->helper->getSearchPattern() != null) {
            $query->setParameter($this->helper->getParameterName('search'), '%' . $this->helper->getSearchPattern() . '%');
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
        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $this->helper->getSearchFields()
        );

        $queryBuilder->justCount($prefix);

        $dql = $queryBuilder->find($prefix);

        $query = $this->getEntityManager()
            ->createQuery($dql);
        if ($this->helper->getSearchPattern() != null) {
            $query->setParameter($this->helper->getParameterName('search'), '%' . $this->helper->getSearchPattern() . '%');
        }

        return intval($query->getSingleResult()['totalcount']);
    }
}
