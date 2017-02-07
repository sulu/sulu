<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * This interface defines the the ListBuilder functionality, for the creation of REST list responses.
 */
interface ListBuilderInterface
{
    const WHERE_COMPARATOR_EQUAL = '=';

    const WHERE_COMPARATOR_UNEQUAL = '!=';

    const WHERE_COMPARATOR_GREATER = '>';

    const WHERE_COMPARATOR_GREATER_THAN = '>=';

    const WHERE_COMPARATOR_LESS = '<';

    const WHERE_COMPARATOR_LESS_THAN = '<=';

    const SORTORDER_ASC = 'ASC';

    const SORTORDER_DESC = 'DESC';

    const CONJUNCTION_AND = 'AND';

    const CONJUNCTION_OR = 'OR';

    /**
     * Sets all the field descriptors for the ListBuilder at once.
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     *
     * @return mixed
     */
    public function setSelectFields($fieldDescriptors);

    /**
     * @deprecated use setSelectFields instead
     */
    public function setFields($fieldDescriptors);

    /**
     * Adds a field descriptor to the ListBuilder, which is then used to retrieve and return the list.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addSelectField(FieldDescriptorInterface $fieldDescriptor);

    /**
     * @deprecated use addSelectField instead
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addField(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Gets a field descriptor used by the ListBuilder to retrieve and return the list.
     *
     * @param string $fieldName
     *
     * @return FieldDescriptorInterface
     */
    public function getSelectField($fieldName);

    /**
     * Checks if field by name has been already added.
     *
     * @param $name
     *
     * @return bool
     */
    public function hasSelectField($name);

    /**
     * @deprecated use hasSelectField instead
     */
    public function hasField($name);

    /**
     * Adds a field descriptor, which will be used for search.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addSearchField(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Sets the search value for the search fields.
     *
     * @param string $search
     *
     * @return ListBuilderInterface
     */
    public function search($search);

    /**
     * Adds a field by which the table is sorted.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param string $order
     *
     * @return ListBuilderInterface
     */
    public function sort(FieldDescriptorInterface $fieldDescriptor, $order = self::SORTORDER_ASC);

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
     * Sets the permission check for the ListBuilder.
     *
     * @param UserInterface $user The user for which the permission must be granted
     * @param int $permission A value from the PermissionTypes
     *
     * @return ListBuilderInterface
     */
    public function setPermissionCheck(UserInterface $user, $permission);

    /**
     * Defines a constraint for the rows to return.
     *
     * @param FieldDescriptorInterface $fieldDescriptor The FieldDescriptor which is checked
     * @param string $value The value the FieldDescriptor should have
     * @param string $comparator The comparator use to compare the values
     *
     * @return ListBuilderInterface
     */
    public function where(
        FieldDescriptorInterface $fieldDescriptor,
        $value,
        $comparator = self::WHERE_COMPARATOR_EQUAL
    );

    /**
     * @deprecated use where instead
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param mixed $value
     *
     * @return ListBuilderInterface
     */
    public function whereNot(FieldDescriptorInterface $fieldDescriptor, $value);

    /**
     * Defines GROUP BY.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     *
     * @return ListBuilderInterface
     */
    public function addGroupBy(FieldDescriptorInterface $fieldDescriptor);

    /**
     * Defines an IN constraint.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param array $values
     */
    public function in(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Defines a between constraint.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param $values
     *
     * @return
     */
    public function between(FieldDescriptorInterface $fieldDescriptor, array $values);

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

    /**
     * Sets an array of field descriptors.
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    public function setFieldDescriptors(array $fieldDescriptors);

    /**
     * Returns a field descriptor by name.
     *
     * @param string $name
     *
     * @return FieldDescriptorInterface|null
     */
    public function getFieldDescriptor($name);

    /**
     * Adds an expression.
     *
     * @param ExpressionInterface $expression
     */
    public function addExpression(ExpressionInterface $expression);

    /**
     * Creates a between expression from the given values.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param array $values
     *
     * @return mixed
     */
    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Creates an in expression from the given values.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param array $values
     *
     * @return mixed
     */
    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values);

    /**
     * Creates an where expression from the given values.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param $value
     * @param string $comparator
     *
     * @return mixed
     */
    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator);

    /**
     * Creates an and expression with the given expressions.
     *
     * @param ExpressionInterface[] $expressions
     *
     * @return ConjunctionExpressionInterface|null
     */
    public function createAndExpression(array $expressions);

    /**
     * Creates an or expressions with the given expressions.
     *
     * @param ExpressionInterface[] $expressions
     *
     * @return ConjunctionExpressionInterface|null
     */
    public function createOrExpression(array $expressions);
}
