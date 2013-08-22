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
     * Searches Entity by filter for fields, pagination and sorted by a column
     * @param array $fields Fields to filter
     * @param integer|null $limit Page size for Pagination
     * @param integer|null $offset Offset for Pagination
     * @param array|null $sorting Columns to sort
     * @param array|null $where Where clauses
     * @param string $prefix Prefix for starting Table
     * @return array Results
     */
    public function findFiltered($fields = null, $limit = null, $offset = null, $sorting = null, $where = array(), $prefix = 'u')
    {
        $dql = $this->getSelectFrom($fields, $where, $prefix);
        $dql = $this->getWhere($where, $prefix, $dql);
        $dql = $this->getOrderBy($sorting, $prefix, $dql);
        $query = $this->getEntityManager()->createQuery($dql)
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return $query->getArrayResult();
    }

    /**
     * Create a Select ... From ... Statement for given fields with joins
     */
    private function getSelectFrom($fields, $where, $prefix)
    {
        $fields = (sizeof($fields) == 0) ? array() : $fields;
        $select = (sizeof($fields) == 0) ? $prefix : "";
        $joins = "";
        $prefixes = array($prefix);

        // select and where fields
        $fields_where = array_merge(($fields != null) ? $fields : array(), array_keys($where));

        if ($fields_where != null && sizeof($fields_where) >= 0) {
            foreach ($fields_where as $field) {
                // Relation name and field delimited by underscore
                $f = explode("_", $field);

                // If field is delimited and is a Relation
                if (sizeof($f) >= 2 && $this->isRelation($f[0])) {
                    $i = 0;
                    $f[-1] = $prefix;
                    // TODO Perhaps isRelation Recursivly?
                    while ($i < sizeof($f) - 2) {
                        if (!in_array($f[$i], $prefixes)) {
                            $i_1 = $i - 1;
                            // JOIN {prefix}.{associationName} {associationPrefix}
                            $joins .= "
                                JOIN $f[$i_1].$f[$i] $f[$i]";
                            $prefixes[] = $f[$i];
                        }
                        $i++;
                    }
                    if (in_array($field, $fields)) {
                        if (strlen($select) > 0) $select .= ', ';
                        // {associationPrefix}.{columnName} {alias}
                        $i_1 = $i - 1;
                        $select .= "$f[$i_1].$f[$i] $field";
                    }
                } else {
                    if (strlen($select) > 0) $select .= ', ';
                    $select .= "$prefix.$field";
                }
            }
        }
        if (strlen($select) == 0) $select = $prefix;

        $dql = "SELECT %s
                FROM %s %s
                  %s";
        return sprintf($dql, $select, $this->getEntityName(), $prefix, $joins);
    }

    /**
     * Check if Field is an Association
     */
    private function isRelation($field)
    {
        return in_array($field, $this->getClassMetadata()->getAssociationNames());
    }

    /**
     * Get DQL for Where clause
     */
    private function getWhere($where, $prefixOrig, $dql)
    {
        if (sizeof($where) > 0) {
            $tmp = "";
            foreach ($where as $k => $w) {
                if (strlen($tmp) > 0) $tmp .= " AND ";
                $prefix = $prefixOrig;
                $col = "";
                $ks = explode("_", $k);
                if (sizeof($ks) == 1) {
                    $col = $ks[0];
                } else {
                    $prefix = $ks[sizeof($ks) - 2];
                    $col = $ks[sizeof($ks) - 1];
                }
                // Add where clause x.y for x_y_z
                $tmp .= "$prefix.$col=$w";
            }
            $format = "%s
                       WHERE %s";
            return sprintf($format, $dql, $tmp);
        }
        return $dql;
    }

    /**
     * Get DQL for Sorting
     */
    private function getOrderBy($sorting, $prefix, $dql)
    {
        // If sorting is defined
        if ($sorting != null && sizeof($sorting) > 0) {
            $orderBy = "";
            // TODO OrderBy relations translations_value
            foreach ($sorting as $col => $dir) {
                if (strlen($orderBy) > 0) $orderBy .= ", ";
                $orderBy .= "$prefix.$col $dir";
            }
            $dql .= "
                ORDER BY $orderBy";
        }
        return $dql;
    }
}