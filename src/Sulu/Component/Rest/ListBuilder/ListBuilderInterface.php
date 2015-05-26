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

/**
 * This interface defines the the ListBuilder functionality, for the creation of REST list responses.
 */
interface ListBuilderInterface
{
    const WHERE_COMPARATOR_EQUAL = '=';

    const WHERE_COMPARATOR_UNEQUAL = '!=';

    const SORTORDER_ASC = 'ASC';

    const SORTORDER_DESC = 'DESC';

    /**
     * Sets all the field descriptors for the ListBuilder at once.
     *
     * @param AbstractFieldDescriptor[] $fieldDescriptors
     *
     * @return mixed
     */
    public function setFields($fieldDescriptors);

    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addField(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Checks if field by name has been already added.
     *
     * @param $name
     *
     * @return bool
     */
    public function hasField($name);

    /**
     * Adds a field descriptor, which will be used for search.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addSearchField(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Sets the search value for the search fields.
     *
     * @param string $search
     *
     * @return ListBuilderInterface
     */
    public function search($search);

    /**
     * Defines the field by which the table is sorted.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param string $order
     *
     * @return ListBuilderInterface
     */
    public function sort(AbstractFieldDescriptor $fieldDescriptor, $order = self::SORTORDER_ASC);

    /**
     * Defines how many items should be returned.
     *
     * @param int $limit
     *
     * @return ListBuilderInterface
     */
    public function limit($limit);

    /**
     * Returns the limit of the builder.
     *
     * @return int
     */
    public function getLimit();

    /**
     * Sets the current page for the builder.
     *
     * @param int $page
     *
     * @return ListBuilderInterface
     */
    public function setCurrentPage($page);

    /**
     * Returns the current page.
     *
     * @return int
     */
    public function getCurrentPage();

    /**
     * Defines a constraint for the rows to return.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor The FieldDescriptor which is checked
     * @param string $value The value the FieldDescriptor should have
     *
     * @return mixed
     */
    public function where(AbstractFieldDescriptor $fieldDescriptor, $value);

    /**
     * Defines a constraint for the rows to return which are not equal the specified values.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor The FieldDescriptor which is checked
     * @param string $value The value the FieldDescriptor should not have
     *
     * @return mixed
     */
    public function whereNot(AbstractFieldDescriptor $fieldDescriptor, $value);

    /**
     * Defines GROUP BY.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     *
     * @return mixed
     */
    public function addGroupBy(AbstractFieldDescriptor $fieldDescriptor);

    /**
     * Defines a in constraint.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param $values
     */
    public function in(AbstractFieldDescriptor $fieldDescriptor, $values);

    /**
     * Defines a between constraint.
     *
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param $values
     */
    public function between(AbstractFieldDescriptor $fieldDescriptor, $values);

    /**
     * The number of total elements for this list.
     *
     * @return int
     */
    public function count();

    /**
     * Returns the objects for the built query.
     *
     * @return mixed
     */
    public function execute();
}
