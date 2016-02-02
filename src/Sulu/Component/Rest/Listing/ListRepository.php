<?php

/*
 * This file is part of Sulu.
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

/**
 * Class ListRepository.
 *
 * @deprecated
 */
class ListRepository extends EntityRepository
{
    /**
     * @var ListRestHelper
     */
    private $helper;

    /**
     * @param ObjectManager  $em
     * @param ClassMetadata  $class
     * @param ListRestHelper $helper
     */
    public function __construct(ObjectManager $em, ClassMetadata $class, ListRestHelper $helper)
    {
        parent::__construct($em, $class);
        $this->helper = $helper;
    }

    /**
     * Find list with parameter.
     *
     * @param array  $where
     * @param string $prefix
     * @param bool   $justCount      Defines, if find should just return the total number of results
     * @param array  $joinConditions optionally specify conditions on join
     *
     * @return array|object|int
     */
    public function find($where = [], $prefix = 'u', $justCount = false, $joinConditions = [])
    {
        $searchPattern = $this->helper->getSearchPattern();
        $searchFields = $this->helper->getSearchFields();

        // if search string is set, but search fields are not, take all fields into account
        if (!is_null($searchPattern) && $searchPattern != '' && (is_null($searchFields) || count($searchFields) == 0)) {
            $searchFields = $this->getEntityManager()->getClassMetadata($this->getEntityName())->getFieldNames();
        }

        $textFields = $this->getFieldsWitTypes(['text', 'string', 'guid'], $searchFields);
        if (is_numeric($searchPattern)) {
            $numberFields = $this->getFieldsWitTypes(['integer', 'float', 'decimal'], $searchFields);
        } else {
            $numberFields = [];
        }

        $queryBuilder = new ListQueryBuilder(
            $this->getClassMetadata()->getAssociationNames(),
            $this->getClassMetadata()->getFieldNames(),
            $this->getEntityName(),
            $this->helper->getFields(),
            $this->helper->getSorting(),
            $where,
            $textFields,
            $numberFields,
            $joinConditions
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
            if (count($searchFields) > 0) {
                if (count($textFields) > 0) {
                    $query->setParameter('search', '%' . $searchPattern . '%');
                }
                if (count($numberFields) > 0) {
                    $query->setParameter('strictSearch', $searchPattern);
                }
            }
        }

        // if just used for counting
        if ($justCount) {
            return intval($query->getSingleResult()['totalcount']);
        }

        $results = $query->getArrayResult();

        // check if relational filter was set ( e.g. emails[0]_email)
        // and filter result
        if (count($filters = $queryBuilder->getRelationalFilters()) > 0) {
            $filteredResults = [];
            // check if fields do contain id, else skip
            if (count($fields = $this->helper->getFields()) > 0 && array_search('id', $fields) !== false) {
                $ids = [];
                foreach ($results as $result) {
                    $id = $result['id'];
                    // check if result already in resultset
                    if (!array_key_exists($id, $ids)) {
                        $ids[$id] = -1;
                        $filteredResults[] = $result;
                    }
                    ++$ids[$id];
                    // check filters
                    foreach ($filters as $filter => $key) {
                        // check if we are at the specified index
                        if ($key == $ids[$id]) {
                            $index = $this->getArrayIndexByKeyValue($filteredResults, $id);
                            // set to current key
                            $filteredResults[$index][$filter] = $result[$filter];
                        }
                    }
                }
                $results = $filteredResults;
            }
        }

        return $results;
    }

    /**
     * returns array index of by a specified key value.
     *
     * @param $array
     * @param $value
     * @param string $key
     *
     * @return bool|int|string
     */
    private function getArrayIndexByKeyValue($array, $value, $key = 'id')
    {
        foreach ($array as $index => $result) {
            if ($result[$key] === $value) {
                return $index;
            }
        }

        return false;
    }

    /**
     * returns the amount of data.
     *
     * @param array  $where
     * @param array  $joinConditions
     * @param string $prefix
     *
     * @return int
     */
    public function getCount($where = [], $joinConditions = [], $prefix = 'u')
    {
        return $this->find($where, $prefix, true, $joinConditions);
    }

    /**
     * returns all fields with a specified type.
     *
     * @param array $types
     * @param null  $intersectArray only return fields that are defined in this array
     *
     * @return array
     */
    public function getFieldsWitTypes(array $types, $intersectArray = null)
    {
        $result = [];
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
