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

use Sulu\Bundle\ResourceBundle\Api\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\OperatorUnknownException;
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

        // when a filter is set
        if ($filterId) {

            $filter = $this->manager->findByIdAndLocale($filterId, $locale);

            if (!$filter) {
                throw new FilterNotFoundException($filterId);
            }

            foreach($filter->getConditionGroups() as $conditionGroup){
                $this->processConditionGroup($conditionGroup, $lb);
            }
        }
    }

    /**
     * Creates a conditions for a condition group
     * @param ConditionGroup $conditionGroup
     * @param ListBuilderInterface $lb
     * @throws OperatorUnknownException
     */
    protected function processConditionGroup(ConditionGroup $conditionGroup, ListBuilderInterface $lb)
    {
        $condition = $conditionGroup->getConditions()[0];
        $fieldDescriptor = $lb->getField($condition->getField());

        // TODO throw exception when fielddescriptor does not exist
//        if(!$fieldDescriptor) {
//            throw new
//        }

        // TODO handle date values like -1 month
        // TODO operator matching


        if(count($conditionGroup->getConditions()) === 1){
            $lb->where($fieldDescriptor, $condition->getValue(), $condition->getOperator());
        } elseif(count($conditionGroup->getConditions()) === 2) {
            $condition2 = $conditionGroup->getConditions()[1];
            switch($condition->getOperator()){
                case 'between':
                    $lb->between($fieldDescriptor, [$condition->getValue(), $condition2->getValue()]);
                    break;
                default:
                    throw new OperatorUnknownException($condition->getOperator());
            }
        }
    }
}
