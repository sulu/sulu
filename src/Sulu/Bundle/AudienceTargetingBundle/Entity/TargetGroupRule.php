<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entity class that holds rules for target groups.
 */
class TargetGroupRule implements TargetGroupRuleInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $frequency;

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup;

    /**
     * @var Collection|TargetGroupConditionInterface[]
     */
    private $conditions;

    /**
     * Initialize collections.
     */
    public function __construct()
    {
        $this->conditions = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getFrequency()
    {
        return $this->frequency;
    }

    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(TargetGroupInterface $targetGroup)
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function addCondition(TargetGroupConditionInterface $condition)
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function removeCondition(TargetGroupConditionInterface $condition)
    {
        $this->conditions->removeElement($condition);

        return $this;
    }

    public function clearConditions()
    {
        $this->conditions->clear();
    }
}
