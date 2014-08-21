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
     * comparator for where conditions
     */
    const WHERE_COMPARATOR_EQUAL = '=';
    const WHERE_COMPARATOR_UNEQUAL = '!=';

    /**
     * The field descriptors for the current list
     * @var AbstractFieldDescriptor[]
     */
    protected $fields = array();

    /**
     * The field descriptors for the field, which will be used for the search
     * @var AbstractFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * The value for which the searchfields will be searched
     * @var string
     */
    protected $search;

    /**
     * The field descriptor for the field to sort
     * @var AbstractFieldDescriptor
     */
    protected $sortField = null;

    /**
     * Defines the sort order of the string
     * @var string
     */
    protected $sortOrder;

    /**
     * The limit for this query
     * @var integer
     */
    protected $limit = null;

    /**
     * The fields to be checked
     * @var array
     */
    protected $whereFields = array();

    /**
     * The values the where fields should have
     * @var array
     */
    protected $whereValues = array();

    /**
     * The where-not fields to be checked
     * @var array
     */
    protected $whereNotFields = array();

    /**
     * The values the where-not fields should have
     * @var array
     */
    protected $whereNotValues = array();

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
        $this->fields[] = $fieldDescriptor;

        return $this;
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
    public function where(AbstractFieldDescriptor $fieldDescriptor, $value)
    {
        $this->whereFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->whereValues[$fieldDescriptor->getName()] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function whereNot(AbstractFieldDescriptor $fieldDescriptor, $value)
    {
        $this->whereNotFields[$fieldDescriptor->getName()] = $fieldDescriptor;
        $this->whereNotValues[$fieldDescriptor->getName()] = $value;
    }
}
