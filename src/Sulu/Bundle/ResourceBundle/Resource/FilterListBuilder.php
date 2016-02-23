<?php

/*
 * This file is part of Sulu.
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
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Component which triggers the generation of additional statements from the conditions of a filter
 * and applies them to the list builder.
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
     * @var ExpressionInterface[]
     */
    protected $expressions = [];

    /**
     * @param FilterManagerInterface $manager
     * @param RequestStack $requestStack
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

            if ($filter->getConjunction()) { // do nothing if no conjunction is set
                foreach ($filter->getConditionGroups() as $conditionGroup) {
                    $this->processConditionGroup($conditionGroup);
                }

                $this->handleCreatedExpressions($this->expressions, $filter->getConjunction());
            }
        }
    }

    /**
     * Handles the previouse created expressions and passes the over to the listbuilder.
     *
     * @param ExpressionInterface[] $expressions
     * @param string $conjunction
     */
    protected function handleCreatedExpressions(array $expressions, $conjunction)
    {
        $expressionCounter = count($expressions);
        switch ($expressionCounter) {
            case 0: // no expressions - nothing to do
                break;
            case 1:
                $this->listBuilder->addExpression($expressions[0]);
                break;
            default:
                $conjunctionExpression = $this->createConjunctionExpression($expressions, $conjunction);
                $this->listBuilder->addExpression($conjunctionExpression);
                break;
        }
    }

    /**
     * Creates a conjunction expression based on the given expressions and conjunction.
     *
     * @param ExpressionInterface[] $expressions
     * @param string $conjunction
     *
     * @return ConjunctionExpressionInterface
     */
    protected function createConjunctionExpression(array $expressions, $conjunction)
    {
        // create the appropriate expression
        if (strtoupper($conjunction) === ListBuilderInterface::CONJUNCTION_AND) {
            return $this->listBuilder->createAndExpression($expressions);
        }

        return $this->listBuilder->createOrExpression($expressions);
    }

    /**
     * Creates a conditions for a condition group.
     *
     * @param ConditionGroup $conditionGroup
     *
     * @throws ConditionFieldNotFoundException
     * @throws FeatureNotImplementedException
     */
    protected function processConditionGroup(ConditionGroup $conditionGroup)
    {
        $condition = $conditionGroup->getConditions()[0];
        $fieldDescriptor = $this->listBuilder->getFieldDescriptor($condition->getField());

        if (!$fieldDescriptor) {
            throw new ConditionFieldNotFoundException($condition->getField());
        }

        if (count($conditionGroup->getConditions()) === 1) {
            $this->createExpression($condition, $fieldDescriptor);
        } elseif (count($conditionGroup->getConditions()) > 1) {
            // TODO implement if needed
            throw new FeatureNotImplementedException('Multiple condition handling not yet implemented!');
        }
    }

    /**
     * Creates expressions from conditions and add them to the expressions array.
     *
     * @param Condition $condition
     * @param AbstractFieldDescriptor $fieldDescriptor
     */
    protected function createExpression(Condition $condition, $fieldDescriptor)
    {
        $value = $this->getValue($condition);

        // relative date for cases like "within a week" or "within this month"
        if ($condition->getOperator() === 'between' && $condition->getType() === DataTypes::DATETIME_TYPE) {
            $this->expressions[] = $this->listBuilder->createBetweenExpression($fieldDescriptor, [$value, new \Datetime()]);
        } else {
            $this->expressions[] = $this->listBuilder->createWhereExpression(
                $fieldDescriptor,
                $value,
                $condition->getOperator()
            );
        }
    }

    /**
     * Parses and returns the value of a condition.
     *
     * @param Condition $condition
     *
     * @return mixed
     *
     * @throws ConditionTypeMismatchException
     */
    protected function getValue(Condition $condition)
    {
        $value = $condition->getValue();
        $type = $condition->getType();

        switch ($type) {
            case DataTypes::UNDEFINED_TYPE:
            case DataTypes::STRING_TYPE:
            case DataTypes::TAGS_TYPE:
            case DataTypes::AUTO_COMPLETE_TYPE:
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
     * Returns boolean value if value is 1, true or "true" otherwise false is returned.
     *
     * @param $value
     *
     * @return bool
     */
    protected function getBoolean($value)
    {
        return $value === 'true' || $value === 1 || $value === true ? true : false;
    }
}
