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

/**
 * Class ListQueryBuilder.
 *
 * @deprecated
 */
class ListQueryBuilder
{
    /**
     * List of all table aliases.
     *
     * @var array
     */
    private $prefixes;

    /**
     * Contains the join part of the dql.
     *
     * @var string
     */
    private $joins;

    /**
     * Contains the select part of the dql.
     *
     * @var string
     */
    private $select;

    /**
     * Containing the fields which should be shown in the result.
     *
     * @var array
     */
    private $fields = [];

    /**
     * The array representation of the sortings.
     *
     * @var array
     */
    private $sorting;

    /**
     * Contains all the where clauses.
     * The keys are the field names and the content is the value to filter for.
     *
     * @var array
     */
    private $where;

    /**
     * The name of the root entity.
     *
     * @var string
     */
    private $entityName;

    /**
     * The names of the relations of the root entity.
     *
     * @var array
     */
    private $associationNames;

    /**
     * cache variable for replacing select string in some cases.
     *
     * @var string
     */
    private $replaceSelect;

    /**
     * defines if query is used for counting.
     *
     * @var bool
     */
    private $countQuery;

    /**
     * The names of columns of the root entity.
     *
     * @var array
     */
    private $fieldNames;

    /**
     * contains all fieldnames that are searched.
     *
     * @var array
     */
    private $searchFields;

    /**
     * contains searched fieldnames that can be queried by LIKE.
     *
     * @var array
     */
    private $searchTextFields;

    /**
     * contains searched fieldnames that are numeric.
     *
     * @var array
     */
    private $searchNumberFields;

    /**
     * used as a storage for saving relational fields.
     *
     * @var
     */
    private $relationalFilters = [];

    /**
     * contains all join conditions.
     *
     * @var
     */
    private $joinConditions = [];

    /**
     * @param $associationNames
     * @param $fieldNames
     * @param $entityName
     * @param $fields
     * @param $sorting
     * @param $where
     * @param array $searchTextFields
     * @param array $searchNumberFields
     * @param array $joinConditions     - specify a custom join condition
     */
    public function __construct(
        $associationNames,
        $fieldNames,
        $entityName,
        $fields,
        $sorting,
        $where,
        $searchTextFields = [],
        $searchNumberFields = [],
        $joinConditions = []
    ) {
        $this->associationNames = $associationNames;
        $this->fieldNames = $fieldNames;
        $this->entityName = $entityName;
        $this->fields = (is_array($fields)) ? $fields : [];
        $this->sorting = $sorting;
        $this->where = $where;
        $this->searchFields = array_merge($searchTextFields, $searchNumberFields);
        $this->searchTextFields = $searchTextFields;
        $this->searchNumberFields = $searchNumberFields;
        $this->joinConditions = $joinConditions;
    }

    /**
     * Searches Entity by filter for fields, pagination and sorted by a column.
     *
     * @param string $prefix Prefix for starting Table
     *
     * @return string
     */
    public function find($prefix = 'u')
    {
        $selectFromDQL = $this->getSelectFrom($prefix);
        $whereDQL = $this->getWhere($prefix);
        if ($this->countQuery != true) {
            $orderDQL = $this->getOrderBy($prefix);
        } else {
            $orderDQL = '';
        }
        $dql = sprintf('%s %s %s', $selectFromDQL, $whereDQL, $orderDQL);

        return $dql;
    }

    /**
     * just return count.
     */
    public function justCount($countAttribute = 'u.id', $alias = 'totalcount')
    {
        $this->countQuery = true;
        $this->replaceSelect = 'COUNT(' . $countAttribute . ') as ' . $alias;
    }

    /**
     * Create a Select ... From ... Statement for given fields with joins.
     *
     * @param string $prefix
     *
     * @return string
     */
    private function getSelectFrom($prefix = 'u')
    {
        $this->joins = '';
        $this->prefixes = [$prefix];

        // select and where fields
        $fieldsWhere = array_merge(
            ($this->fields != null) ? $this->fields : [],
            array_keys($this->where)
        );

        $fieldsWhere = array_merge($fieldsWhere, $this->searchTextFields, $this->searchNumberFields);

        if ($fieldsWhere != null && count($fieldsWhere) >= 0) {
            foreach ($fieldsWhere as $field) {
                $this->performSelectFromField($field, $prefix);
            }
        }
        // if no field is selected take prefix
        if ($this->countQuery === true) {
            $this->select = $this->replaceSelect;
        } elseif (strlen($this->select) == 0) {
            $this->select = $prefix;
        }

        $dql = 'SELECT %s
                FROM %s %s
                  %s';

        return sprintf($dql, $this->select, $this->entityName, $prefix, $this->joins);
    }

    /**
     * solves the relations for a single field and generate dql for select and joins.
     *
     * @param string $field
     * @param string $prefix
     */
    private function performSelectFromField($field, $prefix = 'u')
    {
        // Relation name and field delimited by underscore
        $fieldParts = explode('_', $field);

        // temporary variable for saving field name (needed for array results like [0])
        $realFieldName = $field;
        // check if a certain field number is searched
        if (preg_match('/^(.*)\[(\d+)\]$/', $fieldParts[0], $regresult)) {
            $fieldParts[0] = $regresult[1];
            $realFieldName = implode('_', $fieldParts);
            $this->relationalFilters[$realFieldName] = $regresult[2];
        }

        // If field is delimited and is a Relation
        if (count($fieldParts) >= 2 && $this->isRelation($fieldParts[0])) {
            $this->joins .= $this->generateJoins($fieldParts, $prefix);
            if (in_array($field, $this->fields)) {
                // last element is column name and next-to-last is the associationPrefix
                $i = count($fieldParts) - 1;

                // {associationPrefix}.{columnName} {alias}
                $parent = $fieldParts[$i - 1];
                $tempField = $fieldParts[$i];
                $alias = $realFieldName;

                $this->addToSelect($parent, $tempField, $alias);
            }
        } elseif (in_array($field, $this->fields) && in_array($field, $this->fieldNames)) {
            $this->addToSelect($prefix, $field);
        }
    }

    /**
     * Add {prefix}.{field} {alias} to select string.
     *
     * @param string $prefix
     * @param string $field
     * @param string $alias
     */
    private function addToSelect($prefix, $field, $alias = '')
    {
        if (strlen($this->select) > 0) {
            $this->select .= ', ';
        }
        $this->select .= $this->generateSelect($prefix, $field, $alias);
    }

    /**
     * Generate {prefix}.{field} {alias}.
     *
     * @param string $prefix
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    private function generateSelect($prefix, $field, $alias = '')
    {
        $format = '%s.%s %s';

        return sprintf($format, $prefix, $field, $alias);
    }

    /**
     * Generate JOIN {parent}.{fieldname} {alias} foreach fieldPart.
     *
     * @param array  $fieldParts
     * @param string $prefix
     *
     * @return string
     */
    private function generateJoins($fieldParts, $prefix)
    {
        $i = 0;
        $result = '';
        while ($i <= count($fieldParts) - 2) {
            if (!in_array($fieldParts[$i], $this->prefixes)) {
                $result .= $this->generateJoin(
                    ($i == 0) ? $prefix : $fieldParts[$i - 1],
                    $fieldParts[$i],
                    $fieldParts[$i]
                );
                $this->prefixes[] = $fieldParts[$i];
            }
            ++$i;
        }

        return $result;
    }

    /**
     * Generate JOIN {parent}.{fieldname} {alias}.
     *
     * @param string $parent
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    private function generateJoin($parent, $field, $alias)
    {
        // JOIN {parent}.{associationName} {associationPrefix} [ ON {joinCondition}]
        $format = '
                LEFT JOIN %s.%s %s %s';

        return sprintf($format, $parent, $field, $alias, $this->generateJoinCondition($field));
    }

    /**
     * generates the join condition.
     *
     * @param $field
     *
     * @return string
     */
    private function generateJoinCondition($field)
    {
        if (!array_key_exists($field, $this->joinConditions)) {
            return '';
        }

        // ON {joinConditino}
        $format = ' WITH %s';

        return sprintf($format, $this->joinConditions[$field]);
    }

    /**
     * Check if Field is an Association.
     *
     * @param string $field
     *
     * @return bool
     */
    private function isRelation($field)
    {
        return in_array($field, $this->associationNames);
    }

    /**
     * Get DQL for Where clause.
     *
     * @param string $prefix
     *
     * @return string
     */
    private function getWhere($prefix)
    {
        $result = '';
        // Only return where clause if there actually is some data
        if (count($this->where) > 0 || count($this->searchFields) > 0) {
            $wheres = [];
            $searches = [];

            $whereKeys = array_keys($this->where);

            // Get all fields which will appear in the where clause
            // The search fields already have the right format, and we have to use only the keys of where, because its
            // values contain the filter expression
            $fields = array_unique(array_merge($whereKeys, $this->searchFields));

            foreach ($fields as $key) {
                $keys = explode('_', $key);
                $prefixActual = $prefix;
                if (count($keys) == 1) {
                    $col = $keys[0];
                } else {
                    $i = count($keys);
                    $prefixActual = $keys[$i - 2];
                    $col = $keys[$i - 1];
                }
                // Add where clause y.z for x_y_z
                // FIXME DQL injection?
                if (in_array($key, $whereKeys)) {
                    $wheres[] .= $prefixActual . '.' . $col . ' = ' . $this->where[$key];
                }
                if (in_array($key, $this->searchFields)) {
                    $comparator = '=';
                    $search = ':strictSearch';

                    // search by like
                    if (in_array($key, $this->searchTextFields)) {
                        $comparator = 'LIKE';
                        $search = ':search';
                    }
                    $searches[] .= $prefixActual . '.' . $col . ' ' . $comparator . ' ' . $search;
                }
            }

            // concatenate the query
            if (!empty($wheres)) {
                $result .= implode(' AND ', $wheres);
            }

            if (!empty($searches)) {
                if ($result != '') {
                    $result .= ' AND ';
                }
                $result .= '(' . implode(' OR ', $searches) . ')';
            }

            $result = 'WHERE ' . $result;
        }

        return $result;
    }

    /**
     * Get DQL for Sorting.
     *
     * @param string $prefix
     *
     * @return string
     */
    private function getOrderBy($prefix)
    {
        $result = '';
        // If sorting is defined
        if ($this->sorting != null && count($this->sorting) > 0) {
            $orderBy = '';
            // TODO OrderBy relations translations_value
            foreach ($this->sorting as $col => $dir) {
                if (strlen($orderBy) > 0) {
                    $orderBy .= ', ';
                }
                $orderBy .= $prefix . '.' . $col . ' ' . $dir;
            }
            $result .= '
                ORDER BY ' . $orderBy;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getRelationalFilters()
    {
        return $this->relationalFilters;
    }
}
