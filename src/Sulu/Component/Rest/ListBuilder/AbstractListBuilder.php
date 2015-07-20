<?php

/*
 * This file is part of the Sulu.
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
    protected $selectFields = [];

    /**
     * The field descriptors for the field, which will be used for the search.
     *
     * @var AbstractFieldDescriptor[]
     */
    protected $searchFields = [];

    /**
     * The value for which the searchfields will be searched.
     *
     * @var string
     */
    protected $search;

    /**
     * The field descriptor for the field to sort.
     *
     * @var AbstractFieldDescriptor[]
     */
    protected $sortFields = array();

    /**
     * Defines the sort order of the string.
     *
     * @var string[]
     */
    protected $sortOrders;

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
    protected $whereFields = [];

    /**
     * The values the where fields should have.
     *
     * @var array
     */
    protected $whereValues = [];

    /**
     * The comparators the where fields should use.
     *
     * @var array
     */
    protected $whereComparators = [];

    /**
     * The conjunctions for the where clauses.
     *
     * @var array
     */
    protected $whereConjunctions = [];

    /**
     * group by fields.
     *
     * @var array
     */
    protected $groupByFields = [];

    /**
     * The fields which will be used for in-clauses.
     *
     * @var array
     */
    protected $inFields = [];

    /**
     * The fields which will be used for between-clauses.
     *
     * @var array
     */
    protected $betweenFields = [];

    /**
     * The values for the in-clauses.
     *
     * @var array
     */
    protected $inValues = [];

    /**
     * The values for the between-clauses.
     *
     * @var array
     */
    protected $betweenValues = [];

    /**
     * The conjunctions for the between clauses.
     *
     * @var array
     */
    protected $betweenConjunctions = [];

    /**
     * The page the resulting query will be returning.
     *
     * @var int
     */
    protected $page = 1;

    /**
     * All field descriptors for the current context.
     *
     * @var AbstractFieldDescriptor[]
     */
    protected $fieldDescriptors;

    /**
     * {@inheritDoc}
     */
    public function setSelectFields($fieldDescriptors)
    {
        $this->selectFields = $fieldDescriptors;
    }

    /**
     * @deprecated use setSelectFields instead
     */
    public function setFields($fieldDescriptors)
    {
        $this->selectFields = $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function addSelectField(AbstractFieldDescriptor $fieldDescriptor)
    {
        $this->selectFields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    /**
     * @deprecated use addSelectField instead
     */
    public function addField(AbstractFieldDescriptor $fieldDescriptor)
    {
        $this->selectFields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectField($fieldName)
    {
        if (array_key_exists($fieldName, $this->selectFields)) {
            return $this->selectFields[$fieldName];
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSelectField($name)
    {
        return array_key_exists($name, $this->selectFields);
    }

    /**
     * @deprecated use hasSelectField instead
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->selectFields);
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldDescriptors(array $fieldDescriptors)
    {
        $this->fieldDescriptors = $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDescriptor($fieldName)
    {
        if (array_key_exists($fieldName, $this->fieldDescriptors)) {
            return $this->fieldDescriptors[$fieldName];
        }

        return;
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
        $this->sortFields[] = $fieldDescriptor;
        $this->sortOrders[] = $order;

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
    public function where(AbstractFieldDescriptor $fieldDescriptor, $value, $comparator = self::WHERE_COMPARATOR_EQUAL, $conjunction = self::CONJUNCTION_AND)
    {
        $this->whereFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->whereValues[$fieldDescriptor->getName()] = $value;
        $this->whereComparators[$fieldDescriptor->getName()] = $comparator;
        $this->whereConjunctions[$fieldDescriptor->getName()] = $conjunction;
    }

    /**
     * @deprecated use where instead
     */
    public function whereNot(AbstractFieldDescriptor $fieldDescriptor, $value)
    {
        $this->whereFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->whereValues[$fieldDescriptor->getName()] = $value;
        $this->whereComparators[$fieldDescriptor->getName()] = self::WHERE_COMPARATOR_UNEQUAL;
        $this->whereConjunctions[$fieldDescriptor->getName()] = self::CONJUNCTION_AND;
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
    public function between(AbstractFieldDescriptor $fieldDescriptor, $values, $conjunction = self::CONJUNCTION_AND)
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
