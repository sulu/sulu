<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * ConditionGroup.
 */
class ConditionGroup
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $conditions;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\Filter
     */
    private $filter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->conditions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add conditions.
     *
     * @return ConditionGroup
     */
    public function addCondition(Condition $conditions)
    {
        $this->conditions[] = $conditions;

        return $this;
    }

    /**
     * Remove conditions.
     */
    public function removeCondition(Condition $conditions)
    {
        $this->conditions->removeElement($conditions);
    }

    /**
     * Get conditions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set filter.
     *
     * @return ConditionGroup
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter.
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
