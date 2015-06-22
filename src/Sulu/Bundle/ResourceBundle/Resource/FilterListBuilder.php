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
use Sulu\Bundle\ResourceBundle\Resource\Exception\ConditionFieldNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\ConditionTypeMismatchException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterNotFoundException;
use Sulu\Component\Rest\ListBuilder\AbstractFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Component which triggers the generation of additional statements from the conditions of a filter
 * and applies them to the list builder
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
    protected $filterManager;

    /**
     * @var ListBuilderInterface
     */
    protected $listBuilder;

    /**
     * @param FilterManagerInterface $manager
     * @param RequestStack           $requestStack
     */
    public function __construct(FilterManagerInterface $manager, RequestStack $requestStack)
    {
        $this->filterManager = $manager;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilterToList(ListBuilderInterface $listBuilder)
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request->getLocale();
        $filterId = $request->get('filter');

        $this->listBuilder = $listBuilder;

        // when a filter is set
        if ($filterId) {
            $filter = $this->filterManager->findByIdAndLocale($filterId, $locale);

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
     *
     * @param ConditionGroup $conditionGroup
     * @param string         $conjunction
     *
     * @throws ConditionFieldNotFoundException
     * @throws FeatureNotImplementedException
     */
    protected function processConditionGroup(ConditionGroup $conditionGroup, $conjunction)
    {
        $condition = $conditionGroup->getConditions()[0];
        $fieldDescriptor = $this->listBuilder->getField($condition->getField());

        if (!$fieldDescriptor) {
            throw new ConditionFieldNotFoundException($condition->getField());
        }

        if (count($conditionGroup->getConditions()) === 1) {
            $this->createCondition($condition, $fieldDescriptor, $conjunction);
        } elseif (count($conditionGroup->getConditions()) > 1) {
            // TODO implement if needed
            throw new FeatureNotImplementedException('Multiple condition handling not yet implemented!');
        }
    }

    /**
     * Creates and adds a simple where condition to the listbuilder
     *
     * @param Condition               $condition
     * @param AbstractFieldDescriptor $fieldDescriptor
     * @param string                  $conjunction
     */
    protected function createCondition(Condition $condition, $fieldDescriptor, $conjunction)
    {
        $value = $this->getValue($condition);

        // relative date for cases like "within a week" or "within this month"
        if ($condition->getOperator() === 'between' && $condition->getType() === DataTypes::DATETIME_TYPE) {
            $this->listBuilder->between($fieldDescriptor, [$value, new \Datetime()], $conjunction);
        } else {
            $this->listBuilder->where($fieldDescriptor, $value, $condition->getOperator(), $conjunction);
        }
    }

    /**
     * Parses and returns the value of a condition
     *
     * @param Condition $condition
     *
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
                if (is_numeric($value)) {
                    return floatval($value);
                }
                throw new ConditionTypeMismatchException($condition->getId(), $value, $type);
            case DataTypes::BOOLEAN_TYPE:
                return $this->getBoolean($value);
            case DataTypes::DATETIME_TYPE:
                try {
                    return new \DateTime($value);
                } catch (\Exception $ex) {
                    throw new ConditionTypeMismatchException($condition->getId(), $value, $type);
                }
            default:
                throw new ConditionTypeMismatchException($condition->getId(), $value, $type);
        }
    }

    /**
     * Returns boolean value if value is 1, true or "true" otherwise false is returned
     *
     * @param $value
     *
     * @return boolean
     */
    protected function getBoolean($value)
    {
        return $value === 'true' || $value === 1 || $value === true ? true : false;
    }
}
