<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource;

use Sulu\Bundle\ResourceBundle\Api\Condition;
use Sulu\Bundle\ResourceBundle\Api\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Resource\Exception\ConditionFieldNotFound;
use Sulu\Bundle\ResourceBundle\Resource\Exception\ConditionTypeMismatchException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\OperatorException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\OperatorUnknownException;
use Sulu\Component\Rest\ListBuilder\AbstractFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Component which triggers the generation of additional statements from the conditions of a filter
 * and applies them to the list builder
 *
 * Class FilterListBuilder
 * @package Sulu\Bundle\ResourceBundle\Resource
 */
class FilterListBuilder implements FilterListBuilderInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var FilterManagerInterface
     */
    protected $manager;

    /**
     * @var ListBuilderInterface
     */
    protected $lb;

    /**
     * @param FilterManagerInterface $manager
     * @param RequestStack $requestStack
     */
    public function __construct(FilterManagerInterface $manager, RequestStack $requestStack)
    {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilterToList(ListBuilderInterface $lb)
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request->getLocale();
        $filterId = $request->get('filter');

        $this->lb = $lb;

        // when a filter is set
        if ($filterId) {

            $filter = $this->manager->findByIdAndLocale($filterId, $locale);

            if (!$filter) {
                throw new FilterNotFoundException($filterId);
            }

            foreach ($filter->getConditionGroups() as $conditionGroup) {
                $this->processConditionGroup($conditionGroup, $filter->getConjunction());
            }
        }
    }

    /**
     * Creates a conditions for a condition group
     * @param ConditionGroup $conditionGroup
     * @param string $conjunction
     * @throws FilterException
     * @throws OperatorException
     */
    protected function processConditionGroup(ConditionGroup $conditionGroup, $conjunction)
    {
        $condition = $conditionGroup->getConditions()[0];
        $fieldDescriptor = $this->lb->getField($condition->getField());

        if (!$fieldDescriptor) {
            throw new ConditionFieldNotFound($condition->getField());
        }

        if (count($conditionGroup->getConditions()) === 1) {
            $this->createCondition($condition, $fieldDescriptor, $conjunction);
        } elseif (count($conditionGroup->getConditions()) === 2) {
            $condition2 = $conditionGroup->getConditions()[1];
            switch ($condition->getOperator()) {
                case 'between':
                    $this->createBetweenCondition($condition, $condition2, $fieldDescriptor, $conjunction);
                    break;
                default:
                    throw new OperatorUnknownException($condition->getOperator());
            }
        }
    }

    /**
     * Creates and adds a simple where condition to the listbuilder
     * @param Condition $condition
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param string $conjunction
     */
    protected function createCondition(Condition $condition, $fieldDescriptor, $conjunction)
    {
        // TODO conjunction
        $value = $this->getValue($condition);
        $this->lb->where($fieldDescriptor, $value, $condition->getOperator());
    }

    /**
     * Creates and adds a between condition to the listbuilder
     * @param Condition $condition1
     * @param Condition $condition2
     * @param $fieldDescriptor
     * @param $conjunction
     */
    protected function createBetweenCondition(
        Condition $condition1,
        Condition $condition2,
        $fieldDescriptor,
        $conjunction
    ) {
        // TODO conjunction
        $value1 = $this->getValue($condition1);
        $value2 = $this->getValue($condition2);
        $this->lb->between($fieldDescriptor, [$value1, $value2]);
    }

    /**
     * Parses and returns the value of a condition
     * @param Condition $condition
     * @return mixed
     * @throws ConditionTypeMismatchException
     */
    protected function getValue(Condition $condition)
    {
        $value = $condition->getValue();
        $type = $condition->getType();

        switch ($type) {
            case DataTypes::UNDEFINED_TYPE:
            case DataTypes::STRING_TYPE:
                return $value;
            case DataTypes::NUMBER_TYPE:
                if (is_float($value)) {
                    return floatval($value);
                }
                throw new ConditionTypeMismatchException($condition, $value, $type);
            case DataTypes::BOOLEAN_TYPE:
                if (is_bool($value)) {
                    return boolval($value);
                }
                throw new ConditionTypeMismatchException($condition, $value, $type);
            case DataTypes::DATETIME_TYPE:
                try {
                    return new \DateTime($value);
                } catch (\Exception $ex) {
                    throw new ConditionTypeMismatchException($condition, $value, $type);
                }
            default:
                throw new ConditionTypeMismatchException($condition, $value, $type);
        }
    }
}
