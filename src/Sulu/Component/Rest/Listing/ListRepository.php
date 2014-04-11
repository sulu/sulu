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

        $textFields = $this->getFieldsWitTypes(array('text', 'string', 'guid'), $searchFields);
        if (is_numeric($searchPattern)) {
            $numberFields = $this->getFieldsWitTypes(array('integer', 'float', 'decimal'), $searchFields);
        } else {
            $numberFields = array();
        }

        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getClassMetadata()->getFieldNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $textFields,
            $numberFields
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
            if (sizeof($searchFields) > 0) {
                if (sizeof($textFields) > 0) {
                    $query->setParameter('search', '%' . $searchPattern . '%');
                }
                if (sizeof($numberFields) > 0) {
                    $query->setParameter('strictSearch', $searchPattern);
                }
            }
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

    /**
     * returns all fields with a specified type
     * @param array $types
     * @param null $intersectArray only return fields that are defined in this array
     * @return array
     */
    public function getFieldsWitTypes(array $types, $intersectArray = null)
    {
        $result = array();
        foreach ($this->getClassMetadata()->getFieldNames() as $field) {
            $type = $this->getClassMetadata()->getTypeOfField($field);
            if (in_array($type, $types)) {
                $result[] = $field;
            }
        }
        // calculate intersection
        if (!is_null($intersectArray)) {
            $result = array_intersect($result, $intersectArray);
        }
        return $result;
    }
}
