<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

abstract class AbstractListBuilder implements ListBuilderInterface
{
    /**
     * The field descriptors for the current list.
     *
     * @var AbstractFieldDescriptor[]
     */
    protected $fields = array();

    /**
     * The field descriptors for the field, which will be used for the search.
     *
     * @var AbstractFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * The value for which the searchfields will be searched.
     *
     * @var string
     */
    protected $search;

    /**
     * The field descriptor for the field to sort.
     *
     * @var AbstractFieldDescriptor
     */
    protected $sortField = null;

    /**
     * Defines the sort order of the string.
     *
     * @var string
     */
    protected $sortOrder;

    /**
     * The limit for this query.
     *
     * @var int
     */
    protected $limit = null;

    /**
     * The fields to be checked.
     *
     * @var array
     */
    protected $whereFields = array();

    /**
     * The values the where fields should have.
     *
     * @var array
     */
    protected $whereValues = array();

    /**
     * The comparators the where fields should use
     * @var array
     */
    protected $whereComparators = array();

    /**
     * The conjunctions for the where clauses
     * @var array
     */
    protected $whereConjunctions = array();

    /**
     * group by fields
     * @var array
     */
    protected $groupByFields = array();

    /**
     * The fields which will be used for in-clauses.
     *
     * @var array
     */
    protected $inFields = array();

    /**
     * The fields which will be used for between-clauses.
     *
     * @var array
     */
    protected $betweenFields = array();

    /**
     * The values for the in-clauses.
     *
     * @var array
     */
    protected $inValues = array();

    /**
     * The values for the between-clauses.
     *
     * @var array
     */
    protected $betweenValues = array();

    /**
     * The conjunctions for the between clauses
     * @var array
     */
    protected $betweenConjunctions = array();

    /**
     * The page the resulting query will be returning
     * @var integer
     */
    protected $page = 1;

    /**
     * {@inheritDoc}
     */
    public function setFields($fieldDescriptors)
    {
        $this->fields = $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function addField(AbstractFieldDescriptor $fieldDescriptor)
    {
        $this->fields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getField($fieldName)
    {
        if (array_key_exists($fieldName, $this->fields)) {
            return $this->fields[$fieldName];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * {@inheritDoc}
     */
    public function addSearchField(AbstractFieldDescriptor $fieldDescriptor)
    {
        $this->searchFields[] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function search($search)
    {
        $this->search = $search;
    }

    /**
     * {@inheritDoc}
     */
    public function sort(AbstractFieldDescriptor $fieldDescriptor, $order = self::SORTORDER_ASC)
    {
        $this->sortField = $fieldDescriptor;
        $this->sortOrder = $order;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * {@inheritDoc}
     */
    public function where(AbstractFieldDescriptor $fieldDescriptor, $value, $comparator = self::WHERE_COMPARATOR_EQUAL, $conjunction = self::AND_CONJUNCTION)
    {
        $this->whereFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->whereValues[$fieldDescriptor->getName()] = $value;
        $this->whereComparators[$fieldDescriptor->getName()] = $comparator;
        $this->whereConjunctions[$fieldDescriptor->getName()] = $conjunction;
    }

    /**
     * {@inheritDoc}
     */
    public function in(AbstractFieldDescriptor $fieldDescriptor, $values)
    {
        $this->inFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->inValues[$fieldDescriptor->getName()] = $values;
    }

    /**
     * {@inheritDoc}
     */
    public function between(AbstractFieldDescriptor $fieldDescriptor, $values, $conjunction = self::AND_CONJUNCTION)
    {
        $this->betweenFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->betweenValues[$fieldDescriptor->getName()] = $values;
        $this->betweenConjunctions[$fieldDescriptor->getName()] = $conjunction;
    }

    /**
     * {@inheritDoc}
     */
    public function addGroupBy(AbstractFieldDescriptor $fieldDescriptor)
    {
        $this->groupByFields[$fieldDescriptor->getName()] = $fieldDescriptor;
    }
}
