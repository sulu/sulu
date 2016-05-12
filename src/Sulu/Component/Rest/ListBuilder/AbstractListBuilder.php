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

use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Security\Authentication\UserInterface;

abstract class AbstractListBuilder implements ListBuilderInterface
{
    /**
     * The field descriptors for the current list.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $selectFields = [];

    /**
     * The field descriptors for the field, which will be used for the search.
     *
     * @var FieldDescriptorInterface[]
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
     * @var FieldDescriptorInterface[]
     */
    protected $sortFields = [];

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
     * group by fields.
     *
     * @var array
     */
    protected $groupByFields = [];

    /**
     * The page the resulting query will be returning.
     *
     * @var int
     */
    protected $page = 1;

    /**
     * All field descriptors for the current context.
     *
     * @var FieldDescriptorInterface[]
     */
    protected $fieldDescriptors = [];

    /**
     * @var ExpressionInterface[]
     */
    protected $expressions = [];

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var string
     */
    protected $permission;

    /**
     * {@inheritdoc}
     */
    public function setSelectFields($fieldDescriptors)
    {
        $this->selectFields = array_filter(
            $fieldDescriptors,
            function (FieldDescriptorInterface $fieldDescriptor) {
                if (null === $fieldDescriptor->getMetadata()
                    || !$fieldDescriptor->getMetadata()->has(PropertyMetadata::class)
                ) {
                    return true;
                }

                /** @var PropertyMetadata $propertyMetadata */
                $propertyMetadata = $fieldDescriptor->getMetadata()->get(PropertyMetadata::class);

                return $propertyMetadata->getDisplay() !== PropertyMetadata::DISPLAY_NEVER;
            }
        );
    }

    /**
     * @deprecated use setSelectFields instead
     */
    public function setFields($fieldDescriptors)
    {
        $this->selectFields = $fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function addSelectField(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->selectFields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    /**
     * @deprecated use addSelectField instead
     */
    public function addField(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->selectFields[$fieldDescriptor->getName()] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectField($fieldName)
    {
        if (array_key_exists($fieldName, $this->selectFields)) {
            return $this->selectFields[$fieldName];
        }

        return;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setFieldDescriptors(array $fieldDescriptors)
    {
        $this->fieldDescriptors = $fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptor($fieldName)
    {
        if (array_key_exists($fieldName, $this->fieldDescriptors)) {
            return $this->fieldDescriptors[$fieldName];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function addSearchField(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->searchFields[] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function search($search)
    {
        $this->search = $search;
    }

    /**
     * {@inheritdoc}
     */
    public function sort(FieldDescriptorInterface $fieldDescriptor, $order = self::SORTORDER_ASC)
    {
        $existingIndex = $this->retrieveIndexOfFieldDescriptor($fieldDescriptor, $this->sortFields);

        if ($existingIndex !== false) {
            $this->sortOrders[$existingIndex] = $order;
        } else {
            // Else add to list of sort-fields.
            $this->sortFields[] = $fieldDescriptor;
            $this->sortOrders[] = $order;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissionCheck(UserInterface $user, $permission)
    {
        $this->user = $user;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function where(FieldDescriptorInterface $fieldDescriptor, $value, $comparator = self::WHERE_COMPARATOR_EQUAL)
    {
        $this->expressions[] = $this->createWhereExpression($fieldDescriptor, $value, $comparator);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    /**
     * @deprecated use where instead
     *
     * {@inheritdoc}
     */
    public function whereNot(FieldDescriptorInterface $fieldDescriptor, $value)
    {
        $this->expressions[] = $this->createWhereExpression($fieldDescriptor, $value, self::WHERE_COMPARATOR_UNEQUAL);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    /**
     * {@inheritdoc}
     */
    public function in(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        $this->expressions[] = $this->createInExpression($fieldDescriptor, $values);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    /**
     * {@inheritdoc}
     */
    public function between(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        $this->expressions[] = $this->createBetweenExpression($fieldDescriptor, $values);
        $this->addFieldDescriptor($fieldDescriptor);
    }

    /**
     * Adds a field descriptor.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     */
    protected function addFieldDescriptor(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->fieldDescriptors[$fieldDescriptor->getName()] = $fieldDescriptor;
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy(FieldDescriptorInterface $fieldDescriptor)
    {
        $this->groupByFields[$fieldDescriptor->getName()] = $fieldDescriptor;
    }

    /**
     * {@inheritdoc}
     */
    public function addExpression(ExpressionInterface $expression)
    {
        $this->expressions[] = $expression;
    }

    /**
     * Returns index of given FieldDescriptor in given array of descriptors.
     * If no match is found, false will be returned.
     *
     * @param FieldDescriptorInterface $fieldDescriptor
     * @param FieldDescriptorInterface[] $fieldDescriptors
     *
     * @return bool|int|string
     */
    protected function retrieveIndexOfFieldDescriptor(
        FieldDescriptorInterface $fieldDescriptor,
        array $fieldDescriptors
    ) {
        foreach ($fieldDescriptors as $index => $other) {
            if ($fieldDescriptor->compare($other)) {
                return $index;
            }
        }

        return false;
    }
}
