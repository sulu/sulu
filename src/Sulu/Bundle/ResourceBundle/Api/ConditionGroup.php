<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroup as ConditionGroupEntity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The ConditionGroup class which will be exported to the API.
 *
 * @ExclusionPolicy("all")
 */
class ConditionGroup extends ApiWrapper
{
    /**
     * @param ConditionGroupEntity $entity
     * @param string $locale
     */
    public function __construct(ConditionGroupEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Get id.
     *
     * @VirtualProperty
     * @SerializedName("id")
     *
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get conditions.
     *
     * @VirtualProperty
     * @SerializedName("conditions")
     *
     * @return null|Condition[]
     */
    public function getConditions()
    {
        $conditions = $this->entity->getConditions();
        $result = [];
        if ($conditions) {
            foreach ($conditions as $condition) {
                $result[] = new Condition($condition, $this->locale);
            }

            return $result;
        }

        return;
    }

    /**
     * Add conditions.
     *
     * @param Condition $condition
     *
     * @return ConditionGroup
     */
    public function addCondition(Condition $condition)
    {
        return $this->entity->addCondition($condition->getEntity());
    }

    /**
     * Remove condition.
     *
     * @param Condition $condition
     *
     * @internal param Condition $conditions
     */
    public function removeCondition(Condition $condition)
    {
        $this->entity->removeCondition($condition->getEntity());
    }

    /**
     * Set filter.
     *
     * @param Filter $filter
     */
    public function setFilter(Filter $filter)
    {
        $this->entity->setFilter($filter->getEntity());
    }

    /**
     * Get filter.
     *
     * @return Filter
     */
    public function getFilter()
    {
        return new Filter($this->entity->getFilter(), $this->locale);
    }
}
